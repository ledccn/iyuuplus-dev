<?php

namespace Iyuu\PacificSdk\Contracts;

use Iyuu\PacificSdk\Properties;

/**
 * 数据结构：从服务器获取到的push连接参数
 */
readonly class ResponsePusher
{
    use Properties;

    /**
     * Push长连接服务地址
     * @var string
     */
    public string $url;

    /**
     * 应用密钥
     * @var string
     */
    public string $app_key;

    /**
     * 鉴权地址
     * @var string
     */
    public string $auth;

    /**
     * 用户UID
     * @var int
     */
    public int $uid;
}
