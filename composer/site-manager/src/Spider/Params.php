<?php

namespace Iyuu\SiteManager\Spider;

use ArrayAccess;
use Iyuu\SiteManager\Traits\HasConfig;

/**
 * 爬取参数类
 * @property string $site 站点名称
 * @property string $action 动作
 * @property string $type 任务类型:cookie,rss
 * @property string $uri 统一资源标识符
 * @property string $route 路由规则名称（枚举值）
 * @property string $begin 开始页码
 * @property string $end 结束页码
 * @property int $count 进程数量
 * @property int $sleep 每个种子间休眠的秒数
 * @property bool $daemon 守护进程
 */
class Params implements ArrayAccess
{
    use HasConfig;

    /**
     * 启动时允许的动作
     */
    const array ACTION_LIST = ['start', 'stop', 'restart', 'reload', 'status', 'connections'];

    /**
     * 爬虫类型
     */
    const array TYPE_LIST = ['cookie', 'rss'];

    /**
     * 创建一个爬取参数对象
     * @param string $site
     * @return self
     */
    public static function make(string $site): self
    {
        return new static([
            'site' => $site,
            'type' => 'cookie',
        ]);
    }

    /**
     * 是否为有效的动作
     * @return bool
     */
    public function canValidAction(): bool
    {
        return in_array($this->action, Params::ACTION_LIST);
    }

    /**
     * 当前动作为启动
     * @return bool
     */
    public function isActionEqStart(): bool
    {
        return 'start' === $this->action || 'restart' === $this->action;
    }

    /**
     * 是否为cookie模式
     * @return bool
     */
    public function isTypeEqCookie(): bool
    {
        return 'cookie' === $this->type;
    }

    /**
     * 是否RSS模式
     * @return bool
     */
    public function isTypeEqRss(): bool
    {
        return 'rss' === $this->type;
    }
}
