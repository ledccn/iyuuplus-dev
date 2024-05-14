<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * 代理服务器配置
 */
class OptionsProxy extends Decorator
{
    /**
     * 输出HTML
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label">代理服务器</label>
                <div class="layui-input-block">
                    <input type="text" name="options[curl_opt_proxy]" value="" placeholder="代理地址与端口，英文:分隔，如：192.168.1.2:8080" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">代理验证</label>
                <div class="layui-input-block">
                    <input type="number" name="options[curl_opt_proxy_auth]" value="" placeholder="代理验证字符串，如：username:password" class="layui-input">
                </div>
            </div>

EOF;
        return $html;
    }

    /**
     * 输出输出JavaScript
     * @return string
     */
    public function js(): string
    {
        return $this->generate->js() . PHP_EOL;
    }
}
