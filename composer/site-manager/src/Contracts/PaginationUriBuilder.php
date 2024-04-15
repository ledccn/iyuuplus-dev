<?php

namespace Iyuu\SiteManager\Contracts;

use Iyuu\SiteManager\Spider\RouteEnum;

/**
 * 页面URI构造接口
 */
interface PaginationUriBuilder
{
    /**
     * 构造页面URI
     * @param int $page 页码
     * @param RouteEnum|string|null $route 路由实例或路由枚举值
     * @return string
     */
    public function pageUriBuilder(int $page, RouteEnum|string $route = null): string;

    /**
     * 种子列表页，第一页默认页码
     * @return int
     */
    public function firstPage(): int;

    /**
     * 当前页
     * @return int
     */
    public function currentPage(): int;

    /**
     * 下一页
     * @return int
     */
    public function nextPage(): int;
}
