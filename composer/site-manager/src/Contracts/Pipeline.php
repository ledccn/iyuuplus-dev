<?php

namespace Iyuu\SiteManager\Contracts;

use Iyuu\SiteManager\Spider\Payload;

/**
 * 处理种子的管道接口
 */
interface Pipeline
{
    /**
     * 契约方法
     * @param Payload $payload 有效载荷
     * @param callable $next
     * @return mixed
     */
    public static function process(Payload $payload, callable $next): mixed;
}
