<?php

namespace Iyuu\SiteManager\Contracts;

/**
 * 数据恢复接口
 */
interface RecoveryInterface
{
    /**
     * 数据恢复
     * @param array $list
     * @return bool
     */
    public function recoveryHandle(array $list): bool;
}
