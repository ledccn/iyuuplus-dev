<?php

namespace app\common\cache;

/**
 * 频繁请求缓存
 */
class TooManyRequestsCache extends BaseCache
{
    /**
     * 构造函数
     * @param string $token
     */
    public function __construct(string $token = '')
    {
        $this->setKey(md5($token));
    }
}
