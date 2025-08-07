<?php

namespace app\enums;

use app\traits\HasEventRegister;

/**
 * 自动辅种，事件枚举
 */
enum EventReseedEnums: string
{
    use HasEventRegister;

    /**
     * 下载种子之后
     */
    case reseed_torrent_download_after = 'reseed.torrent.download.after';
    /**
     * 把种子发送给下载器之前
     */
    case reseed_torrent_send_before = 'reseed.torrent.send.before';
    /**
     * 把种子发送给下载器之后
     */
    case reseed_torrent_send_after = 'reseed.torrent.send.after';
    /**
     * 当前客户端辅种开始前
     */
    case reseed_current_before = 'reseed.current.before';
    /**
     * 当前客户端辅种结束后
     */
    case reseed_current_after = 'reseed.current.after';
    /**
     * 全部客户端辅种结束
     */
    case reseed_all_done = 'reseed.all.done';
}
