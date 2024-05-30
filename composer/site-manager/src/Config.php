<?php

namespace Iyuu\SiteManager;

use ArrayAccess;
use InvalidArgumentException;
use Iyuu\SiteManager\Traits\HasConfig;
use Ledc\Curl\Curl;

/**
 * 当前站点配置管理类
 * @property integer $id 主键
 * @property int $sid 站点ID
 * @property string $site 站点名称
 * @property string $nickname 昵称
 * @property string $base_url 域名
 * @property string $mirror 镜像域名
 * @property string $cookie cookie
 * @property bool|int $cookie_required 必须cookie下载种子
 * @property string $download_page 下载种子页
 * @property string $details_page 详情页
 * @property string $reseed_check 检查项
 * @property mixed $options 用户配置值
 * @property integer $disabled 禁用
 * @property integer $is_https 可选：0http，1https，2http+https
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Config implements ArrayAccess
{
    use HasConfig;

    /**
     * 浏览器UA
     */
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36';

    /**
     * 子类初始化
     * @return void
     */
    protected function initialize(): void
    {
        $this->verifyCookie();
    }

    /**
     * 获取用户配置值(options字段内的值)
     * - 支持点 . 分隔符
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getOptions(?string $key = null, mixed $default = null): mixed
    {
        if (null === $key) {
            return $this->get('options', $default);
        }
        return $this->get('options.' . $key, $default);
    }

    /**
     * 获取限速规则
     * @return array|null
     */
    public function getLimit(): ?array
    {
        if ($limit = $this->getOptions('limit')) {
            return $limit;
        }

        return null;
    }

    /**
     * @return void
     */
    protected function verifyCookie(): void
    {
        if ($this->isCookieRequired() && empty($this->cookie)) {
            throw new InvalidArgumentException('缺少cookie配置');
        }
    }

    /**
     * 下载种子是否必须cookie
     * @return bool
     */
    public function isCookieRequired(): bool
    {
        return (bool)$this->get('cookie_required', false);
    }

    /**
     * 获取主机域名或镜像域名
     * - 优先镜像域名
     * @return string
     */
    public function parseDomain(): string
    {
        $protocol = $this->is_https ? 'https://' : 'http://';
        $host = $this->mirror ?: $this->base_url;
        return $protocol . rtrim($host, '/');
    }

    /**
     * 解析生成下载种子的uri
     * - 包括path和queryString部分
     * @return string
     */
    public function parseUri(): string
    {
        return ltrim($this->download_page, '/');
    }

    /**
     * 获取详情uri
     * - 包括path和queryString部分
     * @return string
     */
    public function parseDetailUri(): string
    {
        return ltrim($this->details_page, '/');
    }

    /**
     * 解析生成拼接规则
     * - 返回值如：https=1&ipv4=1
     * @return string
     */
    public function parseJoinQueryString(): string
    {
        return '';
    }

    /**
     * 设置Curl参数
     * - UserAgent、Timeout、Ssl、代理服务器等
     * @param Curl $curl
     * @return void
     */
    public function setCurlOptions(Curl $curl): void
    {
        $curl->setUserAgent($this->get('user_agent', Config::USER_AGENT))
            ->setTimeout(30, 120)
            ->setSslVerify();

        $proxy = $this->get('options.curl_opt_proxy', '');
        $proxyAuth = $this->get('options.curl_opt_proxy_auth', '');
        $curl->setCurlProxy($proxy, $proxyAuth);
    }
}
