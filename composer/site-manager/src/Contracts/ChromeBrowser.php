<?php

namespace Iyuu\SiteManager\Contracts;

use HeadlessChromium\Browser;
use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\Page;

/**
 * 谷歌浏览器回调接口
 */
interface ChromeBrowser
{
    /**
     * 契约方法
     * @param Page $page
     * @param ProcessAwareBrowser|Browser $browser
     * @return mixed
     */
    public function chromeBrowser(Page $page, ProcessAwareBrowser|Browser $browser): mixed;
}
