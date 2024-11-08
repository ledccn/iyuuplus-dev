<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Workerman\Http;


use Revolt\EventLoop;
use RuntimeException;
use Throwable;
use Workerman\Timer;

/**
 * Class Http\Client
 * @package Workerman\Http
 */
#[\AllowDynamicProperties]
class Client
{
    /**
     *
     *[
     *   address=>[
     *        [
     *        'url'=>x,
     *        'address'=>x
     *        'options'=>['method', 'data'=>x, 'success'=>callback, 'error'=>callback, 'headers'=>[..], 'version'=>1.1]
     *        ],
     *        ..
     *   ],
     *   ..
     * ]
     * @var array
     */
    protected $_queue = array();

    /**
     * @var array
     */
    protected $_connectionPool = null;

    /**
     * Client constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_connectionPool = new ConnectionPool($options);
        $this->_connectionPool->on('idle', array($this, 'process'));
    }

    /**
     * Request.
     *
     * @param $url string
     * @param array $options ['method'=>'get', 'data'=>x, 'success'=>callback, 'error'=>callback, 'headers'=>[..], 'version'=>1.1]
     * @return mixed|Response
     * @throws Throwable
     */
    public function request($url, $options = [])
    {
        $options['url'] = $url;
        $needSuspend = !isset($options['success']) && class_exists(EventLoop::class, false);
        try {
            $address = $this->parseAddress($url);
            $this->queuePush($address, ['url' => $url, 'address' => $address, 'options' => &$options]);
            $this->process($address);
        } catch (Throwable $exception) {
            $this->deferError($options, $exception);
            return;
        }
        if ($needSuspend) {
            $suspension = EventLoop::getSuspension();
            $options['success'] = function ($response) use ($suspension) {
                $suspension->resume($response);
            };
            $options['error'] = function ($response) use ($suspension) {
                $suspension->throw($response);
            };
            return $suspension->suspend();
        }
    }

    /**
     * Get.
     *
     * @param $url
     * @param null $success_callback
     * @param null $error_callback
     * @return mixed|Response
     * @throws Throwable
     */
    public function get($url, $success_callback = null, $error_callback = null)
    {
        $options = [];
        if ($success_callback) {
            $options['success'] = $success_callback;
        }
        if ($error_callback) {
            $options['error'] = $error_callback;
        }
        return $this->request($url, $options);
    }

    /**
     * Post.
     *
     * @param $url
     * @param array $data
     * @param null $success_callback
     * @param null $error_callback
     * @return mixed|Response
     * @throws Throwable
     */
    public function post($url, $data = [], $success_callback = null, $error_callback = null)
    {
        $options = [];
        if ($data) {
            $options['data'] = $data;
        }
        if ($success_callback) {
            $options['success'] = $success_callback;
        }
        if ($error_callback) {
            $options['error'] = $error_callback;
        }
        $options['method'] = 'POST';
        return $this->request($url, $options);
    }

    /**
     * Process.
     * User should not call this.
     *
     * @param $address
     * @return void
     * @throws \Exception
     */
    public function process($address)
    {
        $task = $this->queueCurrent($address);
        if (!$task) {
            return;
        }

        $url = $task['url'];
        $address = $task['address'];

        $connection = $this->_connectionPool->fetch($address, strpos($url, 'https') === 0, $task['options']['proxy'] ?? '');
        // No connection is in idle state then wait.
        if (!$connection) {
            return;
        }

        $connection->errorHandler = function(Throwable $exception) use ($task) {
            $this->deferError($task['options'], $exception);
        };
        $this->queuePop($address);
        $options = $task['options'];
        $request = new Request($url);
        $data = isset($options['data']) ? $options['data'] : '';
        if ($data || $data === '0' || $data === 0) {
            $method = isset($options['method']) ? strtoupper($options['method']) : null;
            if ($method && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $request->write($options['data']);
            } else {
                $options['query'] = $data;
            }
        }
        $request->setOptions($options)->attachConnection($connection);

        $client = $this;
        $request->once('success', function($response) use ($task, $client, $request) {
            $client->recycleConnectionFromRequest($request, $response);
            try {
                $new_request = Request::redirect($request, $response);
            } catch (\Exception $exception) {
                $this->deferError($task['options'], $exception);
                return;
            }
            // No redirect.
            if (!$new_request) {
                if (!empty($task['options']['success'])) {
                    call_user_func($task['options']['success'], $response);
                }
                return;
            }

            // Redirect.
            $uri = $new_request->getUri();
            $url = (string)$uri;
            $options = $new_request->getOptions();
            $address = $this->parseAddress($url);
            $task = [
                'url'      => $url,
                'options'  => $options,
                'address'  => $address
            ];
            $this->queueUnshift($address, $task);
            $this->process($address);
        })->once('error', function($exception) use ($task, $client, $request) {
            $client->recycleConnectionFromRequest($request);
            $this->deferError($task['options'], $exception);
        });

        if (isset($options['progress'])) {
            $request->on('progress', $options['progress']);
        }

        $state = $connection->getStatus(false);
        if ($state === 'CLOSING' || $state === 'CLOSED') {
            $connection->reconnect();
        }

        $state = $connection->getStatus(false);
        if ($state === 'CLOSED' || $state === 'CLOSING') {
            return;
        }

        $request->end('');
    }

    /**
     * Recycle connection from request.
     *
     * @param $request Request
     * @param $response Response
     */
    public function recycleConnectionFromRequest($request, $response = null)
    {
        $connection = $request->getConnection();
        if (!$connection) {
            return;
        }
        $connection->onConnect = $connection->onClose = $connection->onMessage = $connection->onError = null;
        $request_header_connection = strtolower($request->getHeaderLine('Connection'));
        $response_header_connection = $response ? strtolower($response->getHeaderLine('Connection')) : '';
        // Close Connection without header Connection: keep-alive
        if ('keep-alive' !== $request_header_connection || 'keep-alive' !== $response_header_connection || $request->getProtocolVersion() !== '1.1') {
            $connection->close();
        }
        $request->detachConnection($connection);
        $this->_connectionPool->recycle($connection);
    }

    /**
     * Parse address from url.
     *
     * @param $url
     * @return string
     */
    protected function parseAddress($url)
    {
        $info = parse_url($url);
        if (empty($info) || !isset($info['host'])) {
            throw new RuntimeException("invalid url: $url");
        }
        $port = isset($info['port']) ? $info['port'] : (strpos($url, 'https') === 0 ? 443 : 80);
        return "tcp://{$info['host']}:{$port}";
    }

    /**
     * Queue push.
     *
     * @param $address
     * @param $task
     */
    protected function queuePush($address, $task)
    {
        if (!isset($this->_queue[$address])) {
            $this->_queue[$address] = [];
        }
        $this->_queue[$address][] = $task;
    }

    /**
     * Queue unshift.
     *
     * @param $address
     * @param $task
     */
    protected function queueUnshift($address, $task)
    {
        if (!isset($this->_queue[$address])) {
            $this->_queue[$address] = [];
        }
        $this->_queue[$address] += [$task];
    }

    /**
     * Queue current item.
     *
     * @param $address
     * @return mixed|null
     */
    protected function queueCurrent($address)
    {
        if (empty($this->_queue[$address])) {
            return null;
        }
        reset($this->_queue[$address]);
        return current($this->_queue[$address]);
    }

    /**
     * Queue pop.
     *
     * @param $address
     */
    protected function queuePop($address)
    {
        unset($this->_queue[$address][key($this->_queue[$address])]);
        if (empty($this->_queue[$address])) {
            unset($this->_queue[$address]);
        }
    }

    /**
     * @param $callback
     * @param $exception
     * @return void
     */
    protected function deferError($options, $exception)
    {
        if (isset($options['error'])) {
            Timer::add(0.000001, $options['error'], [$exception], false);
            return;
        }
        $needSuspend = !isset($options['success']) && class_exists(EventLoop::class, false);
        if ($needSuspend) {
            throw $exception;
        }
    }
}
