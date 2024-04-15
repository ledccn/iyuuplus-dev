<?php

namespace Iyuu\SiteManager\Spider;

use DOMNode;
use DOMNodeList;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Response;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\SiteManager;
use Ledc\Container\App;
use RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * 助手类
 */
class Helper
{
    /**
     * 创建Crawler实例
     * @link https://symfony.com/doc/current/components/dom_crawler.html
     * @param DOMNodeList|DOMNode|array|string|null $html
     * @return Crawler
     */
    public static function makeCrawler(DOMNodeList|DOMNode|array|string|null $html): Crawler
    {
        return new Crawler($html);
    }

    /**
     * 下载种子二进制或生成下载种子的链接
     * @param Torrent $torrent
     * @param bool $metadata
     * @return Response
     * @throws TorrentException
     */
    public static function download(Torrent $torrent, bool $metadata = true): Response
    {
        /** @var SiteManager $siteManager */
        $siteManager = App::pull(SiteManager::class);
        $driver = $siteManager->select($torrent->site);
        if ($metadata) {
            return $driver->download($torrent);
        } else {
            return new Response($driver->downloadLink($torrent), false);
        }
    }

    /**
     * 解析RSS页面
     * @param string $site
     * @param string $url
     * @return array
     */
    public function rss(string $site, string $url): array
    {
        /** @var SiteManager $siteManager */
        $siteManager = App::pull(SiteManager::class);
        $driver = $siteManager->select($site);
        if ($driver instanceof ProcessorXml) {
            return $driver->processXml($url);
        }

        throw new RuntimeException('未实现XML解析接口：' . get_class($driver));
    }

    /**
     * 凭cookie解析HTML列表页
     * @param string $site
     * @param string $url
     * @return array
     * @throws EmptyListException
     */
    public function cookie(string $site, string $url): array
    {
        /** @var SiteManager $siteManager */
        $siteManager = App::pull(SiteManager::class);
        $driver = $siteManager->select($site);
        return $driver->process($url);
    }

    /**
     * 支持的站点列表
     * @param OutputInterface $output
     * @return void
     */
    public static function supportSiteTable(OutputInterface $output): void
    {
        $headers = ['序号', '站点名称', '爬虫', 'RSS订阅', '下载种子元数据', '拼接种子链接', '类名'];
        $rows = SiteManager::supportList();

        $i = 1;
        array_walk($rows, function (&$row) use (&$i) {
            array_unshift($row, $i++);
        });

        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }

    /**
     * 支持的路由规则列表
     * @param OutputInterface $output
     * @return void
     */
    public static function supportRouteTable(OutputInterface $output): void
    {
        $headers = ['路由名称', '路由规则'];
        $rows = [];
        foreach (RouteEnum::cases() as $route) {
            $rows[] = [$route->name, $route->value];
        }

        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }

    /**
     * 清理爬虫缓存
     * @param string $site
     * @return void
     */
    public static function clearRuntimeSpiderCache(string $site): void
    {
        static::deletePageFilename($site);
        static::deleteEmptyListFilename($site);
    }

    /**
     * 删除站点页码文件
     * @param string $site
     * @return bool
     */
    public static function deletePageFilename(string $site): bool
    {
        clearstatcache();
        $file = static::sitePageFilename($site);
        if (is_file($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * 存放站点页码的文件
     * @param string $site
     * @return string
     */
    public static function sitePageFilename(string $site): string
    {
        return runtime_path("/page/$site.page");
    }

    /**
     * 删除空列表页计数文件
     * @param string $site
     * @return bool
     */
    public static function deleteEmptyListFilename(string $site): bool
    {
        clearstatcache();
        $file = static::siteEmptyListFilename($site);
        if (is_file($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * 存放空列表页计数文件
     * @param string $site
     * @return string
     */
    public static function siteEmptyListFilename(string $site): string
    {
        return runtime_path("/page/$site.empty");
    }
}