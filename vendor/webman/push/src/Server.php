<?php
/**
 * This file is part of webman.
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

namespace Webman\Push;

use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Timer;
use Workerman\Worker;


class Server
{
    /**
     * 应用信息
     *
     * @var array
     */
    public $appInfo = [];

    /**
     * 心跳时间
     *
     * @var int
     */
    public $keepAliveTimeout = 60;

    /**
     * api监听的ip端口
     *
     * @var string
     */
    public $apiListen = 'http://0.0.0.0:1080';

    /**
     * webhook 延迟设置
     *
     * @var int
     */
    public $webHookDelay = 3;

    /**
     * @var array
     */
    protected $_globalDataSnapshot = [];

    /**
     * 事件对应的客户端链接
     *
     * @var array
     */
    protected $_eventClients = [];

    /**
     * 所有的客户端链接
     *
     * @var array
     */
    protected $_allClients = [];

    /**
     * array(
     *     'app_key1' => array(
     *         'channel1' => array(
     *             'users' => array(
     *                 'uid1' => array('user_info'=>[], 'ref_count' => x),
     *                 'uid2' => array('user_info'=>[], 'ref_count' => x),
     *             ),
     *             'type' => 'presence',
     *             'subscription_count' => x
     *         ),
     *         'channel2' => array(
     *             'users' => array(
     *                 'uid3' => array('user_info'=>[], 'ref_count' => x)
     *             ),
     *             'type' => 'presence',
     *             'subscription_count' => x
     *         ),
     *      ),
     *      'app_key2' => array(
     *         'channel1' => array(
     *             'type' => 'private',
     *             'subscription_count' => x
     *         ),
     *         'channel2' => array(
     *             'type' => 'public',
     *             'subscription_count' => x
     *         ),
     *      )
     * )
     * @var array
     */
    protected $_globalData = [];

    /**
     * 当前进程全局唯一订阅id
     *
     * @var string
     */
    protected $_globalID = 1;

    /**
     * 构造函数
     *
     * @param string $socket_name
     * @param array $context
     */
    public function __construct($api_listen, $app_info)
    {
        $this->apiListen = $api_listen;
        $this->appInfo = $app_info;
    }

    /**
     * @param $worker
     * @return void
     * @throws \Exception
     */
    public function onWorkerStart($worker)
    {
        $api_worker = new Worker($this->apiListen);
        $api_worker->onMessage = array($this, 'onApiClientMessage');
        $api_worker->listen();
        Timer::add($this->keepAliveTimeout / 2, array($this, 'checkHeartbeat'));
        Timer::add($this->webHookDelay, array($this, 'webHookCheck'));
    }

    /**
     * 客户端连接后
     *
     * @param $connection
     */
    public function onConnect($connection)
    {
        // 客户端有多少次没在规定时间发送心跳
        $connection->clientNotSendPingCount = 0;
        // 设置websocket握手事件回调
        $connection->onWebSocketConnect = array($this, 'onWebSocketConnect');
    }

    /**
     * 当websocket握手时
     * @param TcpConnection $connection
     * @return mixed
     */
    public function onWebSocketConnect(TcpConnection $connection, $header)
    {
        // /app/1234567890abcdefghig?protocol=7&client=js&version=3.2.4&flash=false
        if (!preg_match('/\/app\/([^\/^\?^ ]+)/', (string)$header, $match)) {
            echo "app_key not found\n$header\n";
            $connection->pauseRecv();
            return;
        }

        $app_key = $match[1];
        if (!isset($this->appInfo[$app_key])) {
            echo "Invalid app_key $app_key\n";
            $connection->pauseRecv();
            return;
        }
        $socket_id = $this->createsocketID($connection);
        $connection->appKey = $app_key;
        $connection->socketID = $socket_id;
        $connection->channels = array('' => '');
        $connection->channelUidMap = [];
        $connection->clientNotSendPingCount = 0;
        $this->_eventClients[$app_key][''][$socket_id] = $connection;
        $this->_allClients[$socket_id] = $connection;

        /*
         * 向客户端发送链接成功的消息
         * {"event":"pusher:connection_established","data":"{\"socket_id\":\"208836.27464492\",\"activity_timeout\":120}"}
         */
        $data = array(
            'event' => 'pusher:connection_established',
            'data' => json_encode(array(
                'socket_id' => $socket_id,
                'activity_timeout' => 55
            ))
        );

        $connection->send(json_encode($data));
    }

    /**
     * 客户端关闭链接时
     *
     * @param $connection
     */
    public function onClose($connection)
    {
        if (!isset($connection->socketID)) {
            return;
        }
        $socket_id = $connection->socketID;
        $app_key = $connection->appKey;
        unset($this->_allClients[$socket_id]);
        unset($this->_eventClients[$app_key][''][$socket_id]);

        if (isset($connection->channels)) {
            $app_key = $connection->appKey;
            foreach ($connection->channels as $channel => $uid) {
                if ('' === $channel) {
                    continue;
                }
                if ($uid === '') {
                    $this->unsubscribePublicChannel($connection, $channel);
                } else {
                    $this->unsubscribePresenceChannel($connection, $channel, $uid);
                }
                unset($this->_eventClients[$app_key][$channel][$socket_id]);
            }
        }
    }

    /**
     * 客户端发来消息时
     *
     * @param $connection
     * @param $data
     *
     * @return void
     */
    public function onMessage($connection, $data)
    {
        $connection->clientNotSendPingCount = 0;
        $data = json_decode($data, true);
        if (!$data) {
            return;
        }
        if (!isset($data['event'])) {
            $connection->send($this->error(null, 'Empty event'));
            return;
        }
        $event = $data['event'];
        switch ($event) {
            case 'pusher:ping':
                $connection->send('{"event":"pusher:pong","data":"{}"}');
                return;
            // {"event":"pusher:subscribe","data":{"channel":"my-channel"}}
            case 'pusher:subscribe':
                if (!isset($data['data']['channel'])) {
                    $connection->send($this->error(null, 'Empty channel'));
                    return;
                }
                $channel = $data['data']['channel'];
                // private- 和 presence- 开头的channel需要验证
                $channel_type = $this->getChannelType($channel);
                if ($channel_type === 'presence') {
                    // {"event":"pusher:subscribe","data":{"auth":"b054014693241bcd9c26:10e3b628cb78e8bc4d1f44d47c9294551b446ae6ec10ef113d3d7e84e99763e6","channel_data":"{\"user_id\":100,\"user_info\":{\"name\":\"123\"}}","channel":"presence-channel"}}
                    $client_auth = $data['data']['auth'] ?? '';

                    if (!isset($data['data']['channel_data'])) {
                        $connection->send($this->error(null, 'Empty channel_data'));
                        return;
                    }
                    $auth = $connection->appKey . ':' . hash_hmac('sha256', $connection->socketID . ':' . $channel . ':' . $data['data']['channel_data'], $this->appInfo[$connection->appKey]['app_secret'], false);

                    // {"event":"pusher:error","data":{"code":null,"message":"Received invalid JSON"}}
                    if ($client_auth !== $auth) {
                        return $connection->send($this->error(null, 'Received invalid Auth ' . $auth));
                    }
                    $user_data = json_decode($data['data']['channel_data'], true);
                    if (!$user_data || !isset($user_data['user_id']) || !isset($user_data['user_info'])) {
                        $connection->send($this->error(null, 'Bad channel_data'));
                        return;
                    }

                    $this->subscribePresence($connection, $channel, $user_data['user_id'], $user_data['user_info']);
                    return;

                } elseif ($channel_type === 'private') {
                    // {"event":"pusher:subscribe","data":{"auth":"b054014693241bcd9c26:10e3b628cb78e8bc4d1f44d47c9294551b446ae6ec10ef113d3d7e84e99763e6","channel_data":"{\"user_id\":100,\"user_info\":{\"name\":\"123\"}}","channel":"presence-channel"}}
                    $client_auth = $data['data']['auth'] ?? '';
                    $auth = $connection->appKey . ':' . hash_hmac('sha256', $connection->socketID . ':' . $channel, $this->appInfo[$connection->appKey]['app_secret'], false);
                    // {"event":"pusher:error","data":{"code":null,"message":"Received invalid Auth"}}
                    if ($client_auth !== $auth) {
                        return $connection->send($this->error(null, 'Received invalid Auth ' . $auth));
                    }
                    $this->subscribePrivateChannel($connection, $channel);
                } else {
                    $this->subscribePublicChannel($connection, $channel);
                }

                // {"event":"pusher_internal:subscription_succeeded","data":"{}","channel":"my-channel"}
                $connection->send(json_encode(
                    array(
                        'event' => 'pusher_internal:subscription_succeeded',
                        'data' => '{}',
                        'channel' => $channel
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ));
                return;
            // {"event":"pusher:unsubscribe","data":{"channel":"my-channel"}}
            case 'pusher:unsubscribe':
                $app_key = $connection->appKey;
                if (!isset($data['data']['channel'])) {
                    $connection->send($this->error(null, 'Empty channel'));
                    return;
                }
                $channel = $data['data']['channel'];
                $channel_type = $this->getChannelType($channel);
                switch ($channel_type) {
                    case 'public':
                        $this->unsubscribePublicChannel($connection, $channel);
                        break;
                    case 'private':
                        $this->unsubscribePrivateChannel($connection, $channel);
                        break;
                    case 'presence':
                        $uid = $connection->channels[$channel];
                        $this->unsubscribePresenceChannel($connection, $channel, $uid);
                        break;
                }
                return;

            // {"event":"client-event","data":{"your":"hi"},"channel":"presence-channel"}
            default:
                if (strpos($event, 'pusher:') === 0) {
                    return $connection->send($this->error(null, 'Unknown event'));
                }
                if (!isset($data['channel'])) {
                    $connection->send($this->error(null, 'Empty channel'));
                    return;
                }
                $channel = $data['channel'];
                // 客户端触发事件必须是private 或者 presence的channel
                $channel_type = $this->getChannelType($channel);
                if ($channel_type !== 'private' && $channel_type !== 'presence') {
                    // {"event":"pusher:error","data":{"code":null,"message":"Client event rejected - only supported on private and presence channels"}}
                    return $connection->send($this->error(null, 'Client event rejected - only supported on private and presence channels'));
                }
                // 当前链接没有订阅这个channel
                if (!isset($connection->channels[$channel])) {
                    return $connection->send($this->error(null, 'Client event rejected - you didn\'t subscribe this channel'));
                }
                // 事件必须以client-为前缀
                if (strpos($event, 'client-') !== 0) {
                    return $connection->send($this->error(null, 'Client event rejected - client events must be prefixed by \'client-\''));
                }

                // @todo 检查是否设置了可前端发布事件
                // {"event":"pusher:error","data":{"code":null,"message":"To send client events, you must enable this feature in the Settings page of your dashboard."}}
                // 全局发布事件
                $this->publishToClients($connection->appKey, $channel, $event, json_encode($data['data'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $connection->socketID);
        }
    }


    /**
     * 获得channel类型
     *
     * @param $channel
     * @return string
     */
    protected function getChannelType($channel)
    {
        if (strpos($channel, 'private-') === 0) {
            return 'private';
        } elseif (strpos($channel, 'presence-') === 0) {
            return 'presence';
        }
        return 'public';
    }

    /**
     * 组装失败信息
     *
     * @param $code
     * @param $message
     * @return string
     */
    protected function error($code, $message)
    {
        return json_encode(array('event' => 'pusher:error', 'data' => array('code' => $code, 'message' => $message)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }


    /**
     * 客户端订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function subscribePublicChannel($connection, $channel)
    {
        $app_key = $connection->appKey;
        $connection->channels[$channel] = '';
        $this->_eventClients[$app_key][$channel][$connection->socketID] = $connection;

        if (!isset($this->_globalData[$app_key][$channel])) {
            $this->_globalData[$app_key][$channel] = array(
                'type' => 'presence',
                'subscription_count' => 0
            );
        }

        $this->_globalData[$app_key][$channel]['subscription_count'] += 1;
    }

    /**
     * 客户端订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function subscribePrivateChannel($connection, $channel)
    {
        $this->subscribePublicChannel($connection, $channel);
    }

    /**
     * 客户端订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function subscribePresence($connection, $channel, $uid, $user_info)
    {
        $app_key = $connection->appKey;
        $connection->channels[$channel] = $uid;
        $this->_eventClients[$app_key][$channel][$connection->socketID] = $connection;

        if (!isset($this->_globalData[$app_key][$channel])) {
            $this->_globalData[$app_key][$channel] = array(
                'type' => 'presence',
                'users' => [],
                'subscription_count' => 0
            );
        }
        $this->_globalData[$app_key][$channel]['subscription_count'] += 1;

        $member_added = false;
        if (!isset($this->_globalData[$app_key][$channel]['users'][$uid]['user_info'])) {
            $this->_globalData[$app_key][$channel]['users'][$uid] = array('user_info' => $user_info, 'ref_count' => 0);
            $member_added = true;
        }
        $this->_globalData[$app_key][$channel]['users'][$uid]['ref_count'] += 1;


        $presence_data = $this->getPresenceChannelDataForSubscribe($app_key, $channel);
        if ($member_added) {
            // {"event":"pusher_internal:member_added","data":"{\"user_id\":1488465780,\"user_info\":{\"name\":\"123\",\"sex\":\"1\"}}","channel":"presence-channel"}
            $this->publishToClients($app_key, $channel, 'pusher_internal:member_added', json_encode(array(
                'user_id' => $uid,
                'user_info' => $user_info
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $connection->socketID);
        }

        // {"event":"pusher_internal:subscription_succeeded","data":"{\"presence\":{\"count\":2,\"ids\":[\"1488465780\",\"14884657802\"],\"hash\":{\"1488465780\":{\"name\":\"123\",\"sex\":\"1\"},\"14884657802\":{\"name\":\"123\",\"sex\":\"1\"}}}}","channel":"presence-channel"}
        $connection->send(json_encode(array(
            'event' => 'pusher_internal:subscription_succeeded',
            'data' => json_encode($presence_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'channel' => $channel
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }


    public function getPresenceChannelDataForSubscribe($app_key, $channel)
    {
        $hash = [];
        $count = 100;
        if (isset($this->_globalData[$app_key][$channel])) {
            foreach ($this->_globalData[$app_key][$channel]['users'] as $uid => $item) {
                $hash[$uid] = $item['user_info'];
                if ($count-- <= 0) {
                    break;
                }
            }
            //$hash = array_slice($this->_globalData[$app_key][$channel]['users'], 0, 100, true);
        }
        return array(
            'presence' => array(
                'count' => count($this->_globalData[$app_key][$channel]['users']),
                'ids' => array_keys($hash),
                'hash' => $hash
            )
        );
    }

    /**
     * 客户端取消订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function unsubscribePublicChannel($connection, $channel)
    {
        $app_key = $connection->appKey;
        if (isset($this->_globalData[$app_key][$channel])) {
            $this->_globalData[$app_key][$channel]['subscription_count']--;
            if ($this->_globalData[$app_key][$channel]['subscription_count'] <= 0) {
                unset($this->_globalData[$app_key][$channel]);
            }
        }
        unset($connection->channels[$channel], $this->_eventClients[$connection->appKey][$channel][$connection->socketID]);
    }

    /**
     * 客户端取消订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function unsubscribePrivateChannel($connection, $channel)
    {
        $this->unsubscribePublicChannel($connection, $channel);
    }

    /**
     * 客户端取消订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function unsubscribePresenceChannel($connection, $channel, $uid)
    {
        $app_key = $connection->appKey;
        $member_removed = false;
        if (isset($this->_globalData[$app_key][$channel])) {
            $this->_globalData[$app_key][$channel]['subscription_count']--;
            if ($this->_globalData[$app_key][$channel]['subscription_count'] <= 0) {
                unset($this->_globalData[$app_key][$channel]);
                $member_removed = true;
            } else {
                if (!isset($this->_globalData[$app_key][$channel]['users'][$uid]['ref_count'])) {
                    error_log("\$this->_globalData[$app_key][$channel]['users'][$uid]['ref_count'] not exist\n");
                    return;
                }
                $this->_globalData[$app_key][$channel]['users'][$uid]['ref_count']--;
                $ref_count = $this->_globalData[$app_key][$channel]['users'][$uid]['ref_count'];
                if ($ref_count <= 0) {
                    unset($this->_globalData[$app_key][$channel]['users'][$uid]);
                    $member_removed = true;
                }
            }
            if ($member_removed) {
                // {"event":"pusher_internal:member_removed","data":"{\"user_id\":\"14884657801\"}","channel":"presence-channel"}
                $this->publishToClients($app_key, $channel, 'pusher_internal:member_removed', json_encode(array('user_id' => $uid), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }
        unset($connection->channels[$channel], $this->_eventClients[$connection->appKey][$channel][$connection->socketID]);
    }


    /**
     * 发布事件
     *
     * @param $data
     */
    public function publishToClients($app_key, $channel, $event, $data, $socket_id = null)
    {
        if (!isset($this->_eventClients[$app_key][$channel])) {
            return;
        }
        $data = json_encode(array(
            'event' => $event,
            'data' => $data,
            'channel' => $channel
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        foreach ($this->_eventClients[$app_key][$channel] as $connection) {
            if ($connection->socketID === $socket_id) {
                continue;
            }
            $connection->clientNotSendPingCount = 0;
            // {"event":"my-event","data":"{\"message\":\"hello world\"}","channel":"my-channel"}
            $connection->send($data);
        }
    }


    /**
     * 检查心跳，将心跳超时的客户端关闭
     *
     * @return void
     */
    public function checkHeartbeat()
    {
        foreach ($this->_allClients as $connection) {
            if ($connection->clientNotSendPingCount > 1) {
                $connection->destroy();
            }
            $connection->clientNotSendPingCount++;
        }
    }

    /**
     * 创建一个全局的客户端id
     *
     * @param $connection
     * @return string
     */
    protected function createsocketID($connection)
    {
        $socket_id = "{$this->_globalID}.{$connection->id}";
        return $socket_id;
    }

    /**
     * 创建channel key，用于监听分发给该channel的事件
     *
     * @param $app_key
     * @param $channel
     * @return string
     */
    protected function createChannelKey($app_key, $channel)
    {
        return "$app_key:$channel";
    }

    /**
     * POST /apps/1024/events?auth_key=b054014693241bcd9c26&auth_signature=ed7f5b604e6bbd21a888a861ed536a430a9d5e4df210937a241a811bd17fcf97&auth_timestamp=1487428415&auth_version=1.0&body_md5=15d251b35306a6da7efa515a0e971f80 HTTP/1.1
     * {"name":"my-event","data":"{\"message\":\"hello world\"}","channels":["my-channel"]}
     * {"name":"my-event","data":"{\"message\":\"haha\"}","channels":["my-channel"],"socket_id":"123.456"}
     *
     * GET /apps/1024/channels/my-channel?auth_key=b054014693241bcd9c26&auth_signature=5226650be00a064b417d50d49229e42bbb918e969c42e63aaa63b9d1c6cf9803&auth_timestamp=1489898340&auth_version=1.0
     *
     * GET /apps/1024/channels/presence-channel?auth_key=b054014693241bcd9c26&auth_signature=d46281bf69ccadfe9da270176c85daa88d4b9da55b1f3c2570d48fa1236f0b2c&auth_timestamp=1489903433&auth_version=1.0&info=subscription_count,user_count
     *
     * GET /apps/1024/channels/presence-channel/users?auth_key=b054014693241bcd9c26&auth_signature=2eee0ca6292e17b00484bdcb0bba686a47e8a7365a1b190248946182fc926309&auth_timestamp=1489904560&auth_version=1.0
     */
    public function onApiClientMessage($connection, Request $request)
    {
        if (!($app_key = $request->get('auth_key'))) {
            return $connection->send(new Response(400, [], 'Bad Request'));
        }

        if (!isset($this->appInfo[$app_key])) {
            return $connection->send(new Response(401, [], 'Invalid app_key'));
        }

        $path = $request->path();
        $explode = explode('/', trim($path, '/'));
        if (count($explode) < 3) {
            return $connection->send(new Response(400, [], 'Bad Request'));
        }
        $auth_signature = $request->get('auth_signature');
        $params = $request->get();
        unset($params['auth_signature']);
        ksort($params);
        $string_to_sign = $request->method() . "\n" . $path . "\n" . self::array_implode('=', '&', $params);

        $real_auth_signature = hash_hmac('sha256', $string_to_sign, $this->appInfo[$app_key]['app_secret'], false);
        if ($auth_signature !== $real_auth_signature) {
            return $connection->send(new Response(401, [], 'Invalid signature'));
        }

        $type = $explode[2];
        switch ($type) {
            case 'batch_events':
                $packages = json_decode($request->rawBody(), true);
                if (!$packages || !isset($packages['batch'])) {
                    return $connection->send(new Response(400, [], 'Bad request'));
                }
                $packages = $packages['batch'];
                foreach ($packages as $package) {
                    $channel = $package['channel'];
                    $event = $package['name'];
                    $data = $package['data'];
                    $socket_id = $package['socket_id'] ?? null;
                    $this->publishToClients($app_key, $channel, $event, $data, $socket_id);
                }
                return $connection->send('{}');
                break;
            case 'events':
                $package = json_decode($request->rawBody(), true);
                if (!$package) {
                    return $connection->send(new Response(401, [], 'Invalid signature'));
                }
                $channels = $package['channels'];
                $event = $package['name'];
                $data = $package['data'];
                foreach ($channels as $channel) {
                    $socket_id = $package['socket_id'] ?? null;
                    $this->publishToClients($app_key, $channel, $event, $data, $socket_id);
                }
                return $connection->send('{}');
            case 'channels':
                // info
                $request_info = explode(',', $request->get('info', ''));
                if (!isset($explode[3])) {
                    $channels = [];
                    $prefix = $request->get('filter_by_prefix');
                    $return_subscription_count = in_array('subscription_count', $request_info);
                    foreach ($this->_globalData[$app_key] ?? [] as $channel => $item) {
                        if ($prefix !== null) {
                            if (strpos($channel, $prefix) !== 0) {
                                continue;
                            }
                        }
                        $channels[$channel] = [];
                        if ($return_subscription_count) {
                            $channels[$channel]['subscription_count'] = $item['subscription_count'];
                        }
                    }
                    return $connection->send(json_encode(['channels' => $channels], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
                $channel = $explode[3];
                // users
                if (isset($explode[4])) {
                    if ($explode[4] !== 'users') {
                        return $connection->send(new Response(400, [], 'Bad Request'));
                    }
                    $id_array = isset($this->_globalData[$app_key][$channel]['users']) ?
                        array_keys($this->_globalData[$app_key][$channel]['users']) : array();
                    $user_id_array = array();
                    foreach ($id_array as $id) {
                        $user_id_array[] = array('id' => $id);
                    }

                    $connection->send(json_encode($user_id_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
                $occupied = isset($this->_globalData[$app_key][$channel]);
                $user_count = isset($this->_globalData[$app_key][$channel]['users']) ? count($this->_globalData[$app_key][$channel]['users']) : 0;
                $subscription_count = $occupied ? $this->_globalData[$app_key][$channel]['subscription_count'] : 0;
                $channel_info = array(
                    'occupied' => $occupied
                );
                foreach ($request_info as $name) {
                    switch ($name) {
                        case 'user_count':
                            $channel_info['user_count'] = $user_count;
                            break;
                        case 'subscription_count':
                            $channel_info['subscription_count'] = $subscription_count;
                            break;
                    }
                }
                $connection->send(json_encode($channel_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                break;
            default:
                return $connection->send(new Response(400, [], 'Bad Request'));
        }

    }

    public function webHookCheck()
    {
        $channel_events = [];
        $user_events = [];

        $all_app_keys = array_unique(array_merge(array_keys($this->_globalData), array_keys($this->_globalDataSnapshot)));
        foreach ($all_app_keys as $app_key) {
            if (empty($this->appInfo[$app_key])) {
                continue;
            }
            $snapshot_items = isset($this->_globalDataSnapshot[$app_key]) ? $this->_globalDataSnapshot[$app_key] : [];
            $items = isset($this->_globalData[$app_key]) ? $this->_globalData[$app_key] : [];
            $channels_added = array_diff_key($items, $snapshot_items);
            $channels_removed = array_diff_key($snapshot_items, $items);
            if ($channels_added) {
                $channel_events[$app_key]['channels_added'] = array_keys($channels_added);
            }
            if ($channels_removed) {
                $channel_events[$app_key]['channels_removed'] = array_keys($channels_removed);
            }

            $all_channels = [];
            foreach ($items as $channel => $foo) {
                if ($foo['type'] === 'presence') {
                    $all_channels[$channel] = $channel;
                }
            }
            foreach ($snapshot_items as $channel => $foo) {
                if ($foo['type'] === 'presence' && !isset($all_channels[$channel])) {
                    $all_channels[$channel] = $channel;
                }
            }

            foreach ($all_channels as $channel) {
                $user_array_snapshot = isset($snapshot_items[$channel]['users']) ? $snapshot_items[$channel]['users'] : [];
                $user_array = isset($items[$channel]['users']) ? $items[$channel]['users'] : [];
                $user_added = array_diff_key($user_array, $user_array_snapshot);
                $user_removed = array_diff_key($user_array_snapshot, $user_array);
                if ($user_added) {
                    $user_events[$app_key][$channel]['user_added'] = array_keys($user_added);
                }
                if ($user_removed) {
                    $user_events[$app_key][$channel]['user_removed'] = array_keys($user_removed);
                }
            }
        }

        $this->_globalDataSnapshot = $this->_globalData;

        $this->webHookSend(array('channel_events' => $channel_events, 'user_events' => $user_events));
    }

    protected function webHookSend($data)
    {
        $channel_events = $data['channel_events'];
        $user_events = $data['user_events'];
        $time_ms = microtime(true);
        foreach ($user_events as $app_key => $items) {
            // 没设置user_event回调则忽略
            if (empty($this->appInfo[$app_key]['user_hook'])) {
                continue;
            }
            // {"time_ms":1494300453609,"events":[{"channel":"presence-channel2","user_id":"59094971a","name":"member_added"}]}
            $http_events_body = array(
                'time_ms' => $time_ms,
                'events' => []
            );

            foreach ($items as $channel => $item) {
                if (isset($item['user_added'])) {
                    foreach ($item['user_added'] as $user_id) {
                        $http_events_body['events'][] = array(
                            'channel' => $channel,
                            'user_id' => $user_id,
                            'name' => 'user_added'
                        );
                    }
                }
            }

            foreach ($items as $channel => $item) {
                if (isset($item['user_removed'])) {
                    foreach ($item['user_removed'] as $user_id) {
                        $http_events_body['events'][] = array(
                            'channel' => $channel,
                            'user_id' => $user_id,
                            'name' => 'user_removed'
                        );
                    }
                }
            }

            if ($http_events_body['events']) {
                $this->sendHttpRequest($this->appInfo[$app_key]['user_hook'],
                    $app_key,
                    $this->appInfo[$app_key]['app_secret'],
                    json_encode($http_events_body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        foreach ($channel_events as $app_key => $item) {
            // 没设置channel_event回调则忽略
            if (empty($this->appInfo[$app_key]['channel_hook'])) {
                continue;
            }
            // {"time_ms":1494300446592,"events":[{"channel":"presence-channel2","name":"channel_added"}]}
            $http_events_body = array(
                'time_ms' => $time_ms,
                'events' => []
            );
            if (isset($item['channels_added'])) {
                foreach ($item['channels_added'] as $channel) {
                    $http_events_body['events'][] = array(
                        'channel' => $channel,
                        'name' => 'channel_added'
                    );
                }
            }
            if (isset($item['channels_removed'])) {
                foreach ($item['channels_removed'] as $channel) {
                    $http_events_body['events'][] = array(
                        'channel' => $channel,
                        'name' => 'channel_removed'
                    );
                }
            }
            if ($http_events_body['events']) {
                $this->sendHttpRequest($this->appInfo[$app_key]['channel_hook'],
                    $app_key,
                    $this->appInfo[$app_key]['app_secret'],
                    json_encode($http_events_body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }
    }

    protected function sendHttpRequest($address, $app_key, $secret, $body, $redirect_count = 0)
    {
        $address_info = parse_url($address);
        if (!$address_info) {
            echo new \Exception('bad remote_address');
            return false;
        }

        $scheme = isset($address_info['scheme']) && $address_info['scheme'] === 'https' ? 'ssl' : 'tcp';
        if (!isset($address_info['port'])) {
            $address_info['port'] = $scheme == 'ssl' ? 443 : 80;
        }
        if (!isset($address_info['path'])) {
            $address_info['path'] = '/';
        }
        if (!isset($address_info['query'])) {
            $address_info['query'] = '';
        } else {
            $address_info['query'] = '?' . $address_info['query'];
        }
        $remote_address = "{$address_info['host']}:{$address_info['port']}";
        $remote_host = $address_info['host'];
        $remote_URI = "{$address_info['path']}{$address_info['query']}";
        $signature = hash_hmac('sha256', $body, $secret, false);
        $base_url = $scheme == 'ssl' ? "https://$remote_address/" : "http://$remote_address/";

        $header = "POST $remote_URI HTTP/1.0\r\n";
        $header .= "Host: $remote_host\r\n";
        $header .= "Connection: close\r\n";
        $header .= "X-Pusher-Key: $app_key\r\n";
        $header .= "X-Pusher-Signature: $signature\r\n";
        $header .= "Content-Type: application/json\r\n";
        $header .= "Content-Length: " . strlen($body);
        $http_buffer = $header . "\r\n\r\n" . $body;

        $client = new AsyncTcpConnection('tcp://' . $remote_address);
        if ($scheme == 'ssl') {
            $client->transport = 'ssl';
        }

        $client->onConnect = function ($client) use ($http_buffer) {
            $client->send($http_buffer);
        };
        $client->onMessage = function ($client, $buffer) use ($address, $app_key, $secret, $body, $base_url, $redirect_count) {
            $client->close();
            if (!preg_match("/HTTP\/1\.\d (\d*?) .*?\r\n/", $buffer, $match)) {
                echo "http code not found $buffer\n";
                return;
            }
            $http_code = $match[1];
            $base_code = intval($http_code / 100);
            if ($base_code == 3 && preg_match("/Location: (.*?)\r\n/", $buffer, $match)) {
                if (++$redirect_count > 3) {
                    $msg = date('Y-m-d H:i:s') . "\nURL:$address\nAPP_KEY:$app_key ERR:too many redirect\n$buffer";
                    echo $msg;
                    return;
                }
                $location = $match[1];
                if (strpos($location, 'http://') === 0 || strpos($location, 'https://') === 0) {
                    $this->sendHttpRequest($location, $app_key, $secret, $body, $redirect_count);
                } else {
                    $this->sendHttpRequest($base_url . $location, $app_key, $secret, $body, $redirect_count);
                }
            }
            if ($base_code !== 2) {
                $msg = date('Y-m-d H:i:s') . "\nURL:$address\nAPP_KEY:$app_key\n$buffer";
                echo $msg;
            }
        };
        Timer::add(10, array($client, 'close'), null, false);
        $client->connect();
    }

    /**
     * array_implode
     *
     * @param $glue
     * @param $separator
     * @param $array
     * @return string
     */
    public static function array_implode($glue, $separator, $array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $string = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $string[] = "{$key}{$glue}{$val}";
        }

        return implode($separator, $string);
    }
}

