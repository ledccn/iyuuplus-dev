<?php
/**
 * 事件配置
 */

return [
    // 自动辅种：下载种子之后
    'reseed.torrent.download.after' => [],
    // 自动辅种：把种子发送给下载器之前
    'reseed.torrent.send.before' => [],
    // 自动辅种：把种子发送给下载器之后
    'reseed.torrent.send.after' => [],

    // 自动转移做种客户端：转移前
    'transfer.action.before' => [],
    // 自动转移做种客户端：转移后
    'transfer.action.after' => [],
];
