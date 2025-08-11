<?php

namespace app\common\cache;

/**
 * 辅种缓存
 */
class ReseedCache extends BaseCache
{
    /**
     * 构造函数
     * @param string $site
     */
    public function __construct(string $site = '')
    {
        $this->setKey($site);
    }
}
