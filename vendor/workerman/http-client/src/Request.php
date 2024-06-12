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

use \Workerman\Connection\AsyncTcpConnection;
use \Workerman\Timer;
use Workerman\Psr7\Uri;

/**
 * Class Request
 * @package Workerman\Http
 */
#[\AllowDynamicProperties]
class Request extends \Workerman\Psr7\Request
{
    /**
     * @var AsyncTcpConnection
     */
    protected $_connection = null;

    /**
     * @var Emitter
     */
    protected $_emitter = null;

    /**
     * @var Response
     */
    protected $_response = null;

    /**
     * @var string
     */
    protected $_recvBuffer = '';

    /**
     * @var int
     */
    protected $_expectedLength = 0;

    /**
     * @var int
     */
    protected $_chunkedLength = 0;

    /**
     * @var string
     */
    protected $_chunkedData = '';

    /**
     * @var bool
     */
    protected $_writeable = true;

    /**
     * @var bool
     */
    protected $_selfConnection = false;

    /**
     * @var array
     */
    protected $_options = [
        'allow_redirects' => [
            'max' => 5
        ]
    ];

    /**
     * Request constructor.
     * @param string $url
     */
    public function __construct($url)
    {
        $this->_emitter = new Emitter();
        $headers = [
            'User-Agent' => 'workerman/http-client',
            'Connection' => 'keep-alive'
        ];
        parent::__construct('GET', $url, $headers, '', '1.1');
    }

    /**
     * @param $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @param $event
     * @param $callback
     * @return $this
     */
    public function on($event, $callback)
    {
        $this->_emitter->on($event, $callback);
        return $this;
    }

    /**
     * @param $event
     * @param $callback
     * @return $this
     */
    public function once($event, $callback)
    {
        $this->_emitter->once($event, $callback);
        return $this;
    }

    /**
     * @param $event
     */
    public function emit($event)
    {
        $args = func_get_args();
        call_user_func_array(array($this->_emitter, 'emit'), $args);
    }

    /**
     * @param $event
     * @param $listener
     * @return $this
     */
    public function removeListener($event, $listener)
    {
        $this->_emitter->removeListener($event, $listener);
        return $this;
    }

    /**
     * @param null $event
     * @return $this
     */
    public function removeAllListeners($event = null)
    {
        $this->_emitter->removeAllListeners($event);
        return $this;
    }

    /**
     * @param $event
     * @return $this
     */
    public function listeners($event)
    {
        $this->_emitter->listeners($event);
        return $this;
    }

    /**
     * Connect.
     */
    protected function connect()
    {
        $host = $this->getUri()->getHost();
        $port = $this->getUri()->getPort();
        if (!$port) {
            $port = $this->getDefaultPort();
        }
        $context = array();
        if (!empty( $this->_options['context'])) {
            $context = $this->_options['context'];
        }
        $ssl = $this->getUri()->getScheme() === 'https';
        if (!$ssl) {
            unset($context['ssl']);
        }
        $connection = new AsyncTcpConnection("tcp://$host:$port", $context);
        if ($ssl) {
            $connection->transport = 'ssl';
        }
        $this->attachConnection($connection);
        $this->_selfConnection = true;
        $connection->connect();
    }

    /**
     * @param string $data
     * @return $this
     */
    public function write($data = '')
    {
        if (!$this->writeable()) {
            $this->emitError(new \Exception('Request pending and can not send request again'));
            return $this;
        }

        if (empty($data) && $data !== '0' && $data !== 0) {
            return $this;
        }

        if (is_array($data)) {
            if (isset($data['multipart'])) {
                $multipart = new \Workerman\Psr7\MultipartStream($data['multipart']);
                $this->withHeader('Content-Type', 'multipart/form-data; boundary=' . $multipart->getBoundary());
                $data = $multipart;
            } else {
                $data = http_build_query($data, '', '&');
            }
        }

        $this->getBody()->write($data);
        return $this;
    }

    /**
     * @return void
     */
    public function writeToResponse($buffer)
    {
        $this->emit('progress', $buffer);
        $this->_response->getBody()->write($buffer);
    }

    /**
     * @param string $data
     * @throws \Exception
     */
    public function end($data = '')
    {
        if (isset($this->_options['version'])) {
            $this->withProtocolVersion($this->_options['version']);
        }

        if (isset($this->_options['method'])) {
            $this->withMethod($this->_options['method']);
        }

        if (isset($this->_options['headers'])) {
            $this->withHeaders($this->_options['headers']);
        }

        $query = isset($this->_options['query']) ? $this->_options['query'] : '';
        if ($query || $query === '0') {
            if (is_array($query)) {
                $query = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            }
            $uri = $this->getUri()->withQuery($query);
            $this->withUri($uri);
        }

        if ($data !== '') {
            $this->write($data);
        }

        if ((($data || $data === '0' || $data === 0) || $this->getBody()->getSize()) && !$this->hasHeader('Content-Type')) {
            $this->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        }

        if (!$this->_connection) {
            $this->connect();
        } else {
            if ($this->_connection->getStatus(false) === 'CONNECTING') {
                $this->_connection->onConnect = array($this, 'onConnect');
                return;
            }
            $this->doSend();
        }
    }

    /**
     * @return bool
     */
    public function writeable()
    {
        return $this->_writeable;
    }

    public function doSend()
    {
        if (!$this->writeable()) {
            $this->emitError(new \Exception('Request pending and can not send request again'));
            return;
        }

        $this->_writeable = false;

        $body_size = $this->getBody()->getSize();
        if ($body_size) {
            $this->withHeaders(['Content-Length' => $body_size]);
        }

        $package = \Workerman\Psr7\str($this);
        $this->_connection->send($package);
    }

    public function onConnect()
    {
        try {
            $this->doSend();
        } catch (\Exception $e) {
            $this->emitError($e);
        }
    }

    /**
     * @param $connection
     * @param $recv_buffer
     */
    public function onMessage($connection, $recv_buffer)
    {
        try {
            $this->_recvBuffer .= $recv_buffer;
            if (!strpos($this->_recvBuffer, "\r\n\r\n")) {
                return;
            }

            $response_data = \Workerman\Psr7\_parse_message($this->_recvBuffer);

            if (!preg_match('/^HTTP\/.* [0-9]{3}( .*|$)/', $response_data['start-line'])) {
                throw new \InvalidArgumentException('Invalid response string: ' . $response_data['start-line']);
            }
            $parts = explode(' ', $response_data['start-line'], 3);

            $this->_response = new Response(
                $parts[1],
                $response_data['headers'],
                '',
                explode('/', $parts[0])[1],
                isset($parts[2]) ? $parts[2] : null
            );

            $this->checkComplete($response_data['body']);
        } catch (\Exception $e) {
            $this->emitError($e);
        }
    }

    /**
     * @param $body
     */
    protected function checkComplete($body)
    {
        $status_code = $this->_response->getStatusCode();
        $content_length = $this->_response->getHeaderLine('Content-Length');
        if ($content_length === '0' || ($status_code >= 100 && $status_code < 200)
            || $status_code === 204 || $status_code === 304) {
            $this->emitSuccess();
            return;
        }

        $transfer_encoding = $this->_response->getHeaderLine('Transfer-Encoding');
        // Chunked
        if ($transfer_encoding && false === strpos($transfer_encoding, 'identity')) {
            $this->_connection->onMessage = array($this, 'handleChunkedData');
            $this->handleChunkedData($this->_connection, $body);
        } else {
            $this->_connection->onMessage = array($this, 'handleData');
            $content_length = (int)$this->_response->getHeaderLine('Content-Length');
            if (!$content_length) {
                // Wait close
                $this->_connection->onClose = array($this, 'emitSuccess');
            } else {
                $this->_expectedLength = $content_length;
            }
            $this->handleData($this->_connection, $body);
        }
    }

    /**
     * @param $connection
     * @param $data
     */
    public function handleData($connection, $data)
    {
        try {
            $body = $this->_response->getBody();
            $this->writeToResponse($data);
            if ($this->_expectedLength) {
                $recv_length = $body->getSize();
                if ($this->_expectedLength <= $recv_length) {
                    $this->emitSuccess();
                }
            }
        } catch (\Exception $e) {
            $this->emitError($e);
        }
    }

    /**
     * @param $connection
     * @param $buffer
     */
    public function handleChunkedData($connection, $buffer)
    {
        try {
            if ($buffer !== '') {
                $this->_chunkedData .= $buffer;
            }

            $recv_len = strlen($this->_chunkedData);
            if ($recv_len < 2) {
                return;
            }
            // Get chunked length
            if ($this->_chunkedLength === 0) {
                $crlf_position = strpos($this->_chunkedData, "\r\n");
                if ($crlf_position === false && strlen($this->_chunkedData) > 1024) {
                    $this->emitError(new \Exception('bad chunked length'));
                    return;
                }

                if ($crlf_position === false) {
                    return;
                }
                $length_chunk = substr($this->_chunkedData, 0, $crlf_position);
                if (strpos($crlf_position, ';') !== false) {
                    list($length_chunk) = explode(';', $length_chunk, 2);
                }
                $length = hexdec(ltrim(trim($length_chunk), "0"));
                if ($length === 0) {
                    $this->emitSuccess();
                    return;
                }
                $this->_chunkedLength = $length + 2;
                $this->_chunkedData = substr($this->_chunkedData, $crlf_position + 2);
                $this->handleChunkedData($connection, '');
                return;
            }
            // Get chunked data
            if ($recv_len >= $this->_chunkedLength) {
                $this->writeToResponse(substr($this->_chunkedData, 0, $this->_chunkedLength - 2));
                $this->_chunkedData = substr($this->_chunkedData, $this->_chunkedLength);
                $this->_chunkedLength = 0;
                $this->handleChunkedData($connection, '');
            }
        } catch (\Exception $e) {
            $this->emitError($e);
        }
    }

    /**
     * onError.
     */
    public function onError($connection, $code, $msg)
    {
        $this->emitError(new \Exception($msg, $code));
    }

    /**
     * emitSuccess.
     */
    public function emitSuccess()
    {
        $this->emit('success', $this->_response);
    }

    public function emitError($e)
    {
        try {
            $this->emit('error', $e);
        } finally {
            $this->_connection && $this->_connection->destroy();
        }
    }

    /**
     * @param $request Request
     * @param $response Response
     * @return $this|bool
     */
    public static function redirect($request, $response)
    {
        if (substr($response->getStatusCode(), 0, 1) != '3'
            || !$response->hasHeader('Location')
        ) {
            return false;
        }
        $options = $request->getOptions();
        self::guardMax($options);
        $location = \Workerman\Psr7\UriResolver::resolve(
            $request->getUri(),
            new \Workerman\Psr7\Uri($response->getHeaderLine('Location'))
        );
        \Workerman\Psr7\rewind_body($request);

        $new_request = (new Request($location))->setOptions($options)->withBody($request->getBody());

        return $new_request;
    }

    private static function guardMax(array &$options)
    {
        $current = isset($options['__redirect_count'])
            ? $options['__redirect_count']
            : 0;
        $options['__redirect_count'] = $current + 1;
        $max = $options['allow_redirects']['max'];

        if ($options['__redirect_count'] > $max) {
            throw new \Exception("Too many redirects. will not follow more than {$max} redirects");
        }
    }

    /**
     * onUnexpectClose.
     */
    public function onUnexpectClose()
    {
        $this->emitError(new \Exception('The connection to ' . $this->_connection->getRemoteIp() . ' has been closed.'));
    }

    /**
     * @return int
     */
    protected function getDefaultPort()
    {
        return ('https' === $this->getUri()->getScheme()) ? 443 : 80;
    }

    /**
     * detachConnection.
     *
     * @return void
     */
    public function detachConnection()
    {
        $this->cleanConnection();
        // 不是连接池的连接则断开
        if ($this->_selfConnection) {
            $this->_connection->close();
            return;
        }
        $this->_writeable = true;
    }

    /**
     * @return \Workerman\Connection\AsyncTcpConnection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * attachConnection.
     *
     * @param $connection \Workerman\Connection\AsyncTcpConnection
     * @return $this
     */
    public function attachConnection($connection)
    {
        $connection->onConnect = array($this, 'onConnect');
        $connection->onMessage = array($this, 'onMessage');
        $connection->onError   = array($this, 'onError');
        $connection->onClose   = array($this, 'onUnexpectClose');
        $this->_connection = $connection;

        return $this;
    }

    /**
     * cleanConnection.
     */
    protected function cleanConnection()
    {
        $connection = $this->_connection;
        $connection->onConnect = $connection->onMessage = $connection->onError =
        $connection->onClose = $connection->onBufferFull = $connection->onBufferDrain = null;
        $this->_connection = null;
        $this->_emitter->removeAllListeners();
    }
}
