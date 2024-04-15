<?php

namespace Iyuu\SiteManager\Spider\Context;

use support\Context;

/**
 * 当前页
 */
class CurrentPage
{
    /**
     * KEY
     */
    private const KEY = 'currentPage';

    /**
     * 设置
     * @param int $page
     * @return void
     */
    public static function set(int $page): void
    {
        Context::set(self::KEY, $page);
    }

    /**
     * 获取
     * @return int|null
     */
    public static function get(): ?int
    {
        return Context::get(self::KEY);
    }

    /**
     * 删除
     * @return void
     */
    public static function delete(): void
    {
        Context::delete(self::KEY);
    }
}