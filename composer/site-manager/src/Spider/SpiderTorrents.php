<?php

namespace Iyuu\SiteManager\Spider;

use Error;
use Exception;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Observer;
use Iyuu\SiteManager\Contracts\Reseed;
use Iyuu\SiteManager\Traits\HasConfig;
use RuntimeException;
use Throwable;

/**
 * 爬虫使用的种子对象
 * @property int $id 种子ID
 * @property string $h1 主标题
 * @property string $title 副标题
 * @property string $details 详情页
 * @property string $download 种子下载链接
 * @property string $filename 文件名
 * @property string $type 促销类型
 * @property ?int $group_id 种子分组ID（特有字段：海豚、海报、皮等）
 * @property ?string $metadata 种子元数据
 */
class SpiderTorrents
{
    use HasConfig;

    /**
     * 解码器
     */
    const DECODER = '\\runtime\\Bencode';

    /**
     * 观察者
     * @var Observer[]
     */
    private static array $observers = [];

    /**
     * 下载种子是否需要cookie
     * @var bool
     */
    protected bool $cookieRequired = true;

    /**
     * 添加观察者
     * @param string $observer
     * @return void
     */
    final public static function observer(string $observer): void
    {
        if (!is_subclass_of($observer, Observer::class, true)) {
            throw new RuntimeException('未实现观察者接口');
        }
        //去重
        if (!in_array($observer, self::$observers, true)) {
            self::$observers[] = $observer;
        }
    }

    /**
     * 通知所有观察者
     * @param array $items 种子列表数组
     * @param BaseDriver $baseDriver 站点对象
     * @param bool $cookieRequired 下载种子时必须cookie
     * @return void
     */
    final public static function notify(array $items, BaseDriver $baseDriver, bool $cookieRequired = true): void
    {
        if (empty($items)) {
            return;
        }

        foreach ($items as $key => $item) {
            $spiderTorrents = new static($item);
            $spiderTorrents->setCookieRequired($cookieRequired);
            foreach (self::$observers as $observer) {
                try {
                    $observer::update($spiderTorrents, $baseDriver, $key);
                } catch (Error|Exception|Throwable $throwable) {
                }
            }
        }
    }

    /**
     * 判断是否存在哈希解码器
     * @return bool
     */
    final public static function existsDecoder(): bool
    {
        return class_exists(self::DECODER) && is_subclass_of(self::DECODER, Reseed::class, true);
    }

    /**
     * 判断下载种子是否需要cookie
     * @return bool
     */
    public function isCookieRequired(): bool
    {
        return $this->cookieRequired;
    }

    /**
     * 设置下载种子是否需要cookie
     * @param bool $cookieRequired 下载种子是否需要cookie
     */
    public function setCookieRequired(bool $cookieRequired): void
    {
        $this->cookieRequired = $cookieRequired;
    }
}
