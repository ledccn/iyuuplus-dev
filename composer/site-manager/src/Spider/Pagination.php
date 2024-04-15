<?php

namespace Iyuu\SiteManager\Spider;

use Iyuu\SiteManager\Spider\Context\CurrentPage;
use Iyuu\SiteManager\Utils;

/**
 * 站点分页组件
 */
trait Pagination
{
    /**
     * 下一页
     * @param int $step 翻页每页递增的值
     * @return int
     */
    public function nextPage(int $step = 1): int
    {
        $current_page = $this->currentPage();
        $next_page = $current_page + $step;
        $file = $this->sitePageFilename();
        file_put_contents($file, $next_page);

        $worker_id = SpiderWorker::getParams()->canValidAction() ? SpiderWorker::getWorker()->id : 0;
        echo SpiderWorker::getParams()->site . ' ｜ Worker进程ID：' . $worker_id . ' ｜ 当前页码：' . $current_page . ' ｜ 下一页：' . $next_page . PHP_EOL . PHP_EOL;

        return $current_page;
    }

    /**
     * 当前页
     * @return int
     */
    public function currentPage(): int
    {
        if (!SpiderWorker::getParams()->canValidAction()) {
            return CurrentPage::get() ?: $this->getBeginPage();
        }

        clearstatcache();
        $file = $this->sitePageFilename();
        if (is_file($file)) {
            $current_page = file_get_contents($file, false, null);
            return (int)$current_page;
        }

        $path = dirname($file);
        if (!is_dir($path)) {
            Utils::createDir($path);
        }
        $page = $this->getBeginPage();
        file_put_contents($file, $page);
        return $page;
    }

    /**
     * 获取开始页码
     * @return int
     */
    protected function getBeginPage(): int
    {
        $page = SpiderWorker::getParams()->begin;
        return ctype_digit($page) ? (int)$page : $this->firstPage();
    }

    /**
     * 存放站点页码的文件
     * @return string
     */
    private function sitePageFilename(): string
    {
        $site = SpiderWorker::getParams()->site;
        return Helper::sitePageFilename($site);
    }
}
