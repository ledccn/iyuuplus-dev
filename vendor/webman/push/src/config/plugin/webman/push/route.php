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

use support\Request;
use Webman\Route;
use Webman\Push\Api;

/**
 * 推送js客户端文件
 */
Route::get('/plugin/webman/push/push.js', function (Request $request) {
    return response()->file(base_path().'/vendor/webman/push/src/push.js');
});

/**
 * 私有频道鉴权，这里应该使用session辨别当前用户身份，然后确定该用户是否有权限监听channel_name
 */
Route::post(config('plugin.webman.push.app.auth'), function (Request $request) {
    $pusher = new Api(str_replace('0.0.0.0', '127.0.0.1', config('plugin.webman.push.app.api')), config('plugin.webman.push.app.app_key'), config('plugin.webman.push.app.app_secret'));
    $channel_name = $request->post('channel_name');
    $session = $request->session();
    // 这里应该通过session和channel_name判断当前用户是否有权限监听channel_name
    $has_authority = true;
    if ($has_authority) {
        return response($pusher->socketAuth($channel_name, $request->post('socket_id')));
    } else {
        return response('Forbidden', 403);
    }
});

/**
 * 当频道上线以及下线时触发的回调
 * 频道上线：是指某个频道从没有连接在线到有连接在线的事件
 * 频道下线：是指某个频道的所有连接都断开触发的事件
 */
Route::post(parse_url(config('plugin.webman.push.app.channel_hook'), PHP_URL_PATH), function (Request $request) {

    // 没有x-pusher-signature头视为伪造请求
    if (!$webhook_signature = $request->header('x-pusher-signature')) {
        return response('401 Not authenticated', 401);
    }

    $body = $request->rawBody();

    // 计算签名，$app_secret 是双方使用的密钥，是保密的，外部无从得知
    $expected_signature = hash_hmac('sha256', $body, config('plugin.webman.push.app.app_secret'), false);

    // 安全校验，如果签名不一致可能是伪造的请求，返回401状态码
    if ($webhook_signature !== $expected_signature) {
        return response('401 Not authenticated', 401);
    }

    // 这里存储这上线 下线的channel数据
    $payload = json_decode($body, true);

    $channels_online = $channels_offline = [];

    foreach ($payload['events'] as $event) {
        if ($event['name'] === 'channel_added') {
            $channels_online[] = $event['channel'];
        } else if ($event['name'] === 'channel_removed') {
            $channels_offline[] = $event['channel'];
        }
    }

    // 业务根据需要处理上下线的channel，例如将在线状态写入数据库，通知其它channel等
    // 上线的所有channel
    echo 'online channels: ' . implode(',', $channels_online) . "\n";
    // 下线的所有channel
    echo 'offline channels: ' . implode(',', $channels_offline) . "\n";

    return 'OK';
});



