<?php
/**
 * 事件配置
 */

use app\enums\EventReseedEnums;
use app\enums\EventTransferEnums;

return [
    // 自动辅种：下载种子之后
    EventReseedEnums::reseed_torrent_download_after->value => [],
    // 自动辅种：把种子发送给下载器之前
    EventReseedEnums::reseed_torrent_send_before->value => [],
    // 自动辅种：把种子发送给下载器之后
    EventReseedEnums::reseed_torrent_send_after->value => [],
    // 自动辅种：当前客户端辅种开始前
    EventReseedEnums::reseed_current_before->value => [],
    // 自动辅种：当前客户端辅种结束后
    EventReseedEnums::reseed_current_after->value => [],
    // 自动辅种：全部客户端辅种结束
    EventReseedEnums::reseed_all_done->value => [],

    // 自动转移做种客户端：转移前
    EventTransferEnums::transfer_action_before->value => [],
    // 自动转移做种客户端：转移后
    EventTransferEnums::transfer_action_after->value => [],
];
