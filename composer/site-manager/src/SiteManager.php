<?php

namespace Iyuu\SiteManager;

use Iyuu\SiteManager\Contracts\ConfigInterface;
use Iyuu\SiteManager\Contracts\DownloaderInterface;
use Iyuu\SiteManager\Contracts\DownloaderLinkInterface;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Response;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\TorrentException;
use Ledc\Container\Manager;
use think\helper\Str;
use Throwable;

/**
 * 站点管理器类
 */
class SiteManager extends Manager implements DownloaderInterface
{
    /**
     * 驱动的目录名称和类前缀
     */
    public const string DRIVER_PREFIX = 'Driver';
    /**
     * 驱动的命名空间
     */
    private const string DRIVER_NAMESPACE = __NAMESPACE__ . '\\Driver\\';
    /**
     * 驱动的命名空间（包含类前缀）
     * @var string|null
     */
    protected ?string $namespace = self::DRIVER_NAMESPACE . self::DRIVER_PREFIX;
    /**
     * 始终创建新的驱动对象实例
     * @var bool
     */
    protected bool $alwaysNewInstance = true;

    /**
     * 构造函数
     * @param ConfigInterface $config 当前站点配置
     */
    public function __construct(public readonly ConfigInterface $config)
    {
    }

    /**
     * 【置空】默认驱动
     * @return string|null
     */
    public function getDefaultDriver(): ?string
    {
        return null;
    }

    /**
     * 获取驱动配置
     * @param string $name
     * @return mixed
     */
    protected function resolveConfig(string $name): array
    {
        return $this->config->get($name);
    }

    /**
     * 选择站点
     * @param string $name
     * @return BaseDriver
     */
    public function select(string $name): BaseDriver
    {
        return $this->driver($name);
    }

    /**
     * 下载种子
     * @param Torrent $torrent
     * @return mixed
     * @throws TorrentException
     */
    public function download(Torrent $torrent): Response
    {
        return $this->select($torrent->site)->download($torrent);
    }

    /**
     * 站点名称转换为类名
     * @param string $site
     * @return string
     */
    public static function siteToClassname(string $site): string
    {
        return self::DRIVER_PREFIX . Str::studly($site);
    }

    /**
     * 获取当前目录
     * @return string
     */
    public static function getDirname(): string
    {
        return __DIR__;
    }

    /**
     * 获取当前类的命名空间
     * @return string
     */
    public static function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    /**
     * 获取站点的支持情况
     * @param bool $isBoolean
     * @return array
     */
    public static function supportList(bool $isBoolean = false): array
    {
        $rows = [];
        $pattern = implode(DIRECTORY_SEPARATOR, [
            self::getDirname(),
            self::DRIVER_PREFIX,
            self::DRIVER_PREFIX . '*.php'
        ]);
        foreach (glob($pattern) as $filename) {
            try {
                $classname = basename($filename, '.php');
                $class = self::DRIVER_NAMESPACE . $classname;
                $site = $class::SITE_NAME;
                $supportProcessor = is_subclass_of($class, Processor::class);
                $supportProcessorXml = is_subclass_of($class, ProcessorXml::class);
                $supportDownloaderInterface = is_subclass_of($class, DownloaderInterface::class);
                $supportDownloaderLinkInterface = is_subclass_of($class, DownloaderLinkInterface::class);
                $rows[$site] = [
                    // 站点名称
                    $site,
                    // 爬虫
                    $isBoolean ? $supportProcessor : ($supportProcessor ? 'Yes' : ''),
                    // RSS订阅
                    $isBoolean ? $supportProcessorXml : ($supportProcessorXml ? 'Yes' : ''),
                    // 下载种子元数据
                    $isBoolean ? $supportDownloaderInterface : ($supportDownloaderInterface ? 'Yes' : ''),
                    // 拼接种子链接
                    $isBoolean ? $supportDownloaderLinkInterface : ($supportDownloaderLinkInterface ? 'Yes' : ''),
                    // 类名
                    $class
                ];
            } catch (Throwable $throwable) {
            }
        }

        //多维数组排序
        $_site = [];
        foreach ($rows as $key => $value) {
            $_site[$key] = $value[0];
        }
        array_multisort($_site, SORT_ASC, $rows);

        return $rows;
    }
}
