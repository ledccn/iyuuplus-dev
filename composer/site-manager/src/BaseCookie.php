<?php

namespace Iyuu\SiteManager;

use InvalidArgumentException;
use Iyuu\SiteManager\Contracts\PaginationUriBuilder;
use Iyuu\SiteManager\Contracts\Processor;
use think\helper\Str;

/**
 * 凭cookie解析HTML列表页
 */
abstract class BaseCookie implements Processor, PaginationUriBuilder
{
    /**
     * 子类的命名空间
     */
    public const NAMESPACE = __NAMESPACE__ . '\\Cookie\\';

    /**
     * 子类的类名前缀
     */
    public const CLASS_PREFIX = 'Cookie';

    /**
     * 构造函数
     * @param BaseDriver $baseDriver
     */
    final public function __construct(public readonly BaseDriver $baseDriver)
    {
        if (empty($this->baseDriver->getConfig()->cookie)) {
            throw new InvalidArgumentException('cookie为空，无法解析HTML页面');
        }
        $this->initialize();
    }

    /**
     * 子类初始化
     * @return void
     */
    protected function initialize(): void
    {
    }

    /**
     * 爬虫模式：是否必须cookie才能下载种子
     * @return bool
     */
    protected function isSpiderDownloadCookieRequired(): bool
    {
        return true;
    }

    /**
     * 获取当前站点配置
     * @return Config
     */
    final public function getConfig(): Config
    {
        return $this->baseDriver->getConfig();
    }

    /**
     * 站点名称转换为类名
     * @param string $site
     * @return string
     */
    public static function siteToClassname(string $site): string
    {
        return self::CLASS_PREFIX . Str::studly($site);
    }

    /**
     * 站点名称转换为完整类名（包含命名空间）
     * @param string $site
     * @return string
     */
    public static function siteToClass(string $site): string
    {
        return self::NAMESPACE . self::siteToClassname($site);
    }

    /**
     * 替换过滤空白字符
     * @param string $string
     * @return string
     */
    protected function normalizeWhitespace(string $string): string
    {
        static $disallow = ["\0", "\r", "\n", " "];
        $string = strip_tags(str_replace($disallow, '', $string));
        return trim(preg_replace("/(?:[ \n\r\t\x0C]{2,}+|[\n\r\t\x0C])/", ' ', $string), " \n\r\t\x0C");
    }

    /**
     * 爬虫的周期性定时任务，结束页码
     * - 设置uri时，仅爬取uri指定的单页
     * - 不设置uri时，才能使用当前方法
     * @return int
     */
    public function crontabEndPage(): int
    {
        return 2;
    }
}
