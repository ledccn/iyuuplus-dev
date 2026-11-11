<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * FluxTorrent
 */
class FluxTorrent extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label required">订阅令牌</label>
                <div class="layui-input-block">
                    <input type="text" name="options[rsskey]" value="" required lay-verify="required" placeholder="请输入 fxr_ 开头的订阅令牌（个人设置 - 订阅 里获取）" class="layui-input" lay-affix="eye">
                </div>
            </div>

EOF;
        return $html;
    }

    /**
     * @return string
     */
    public function js(): string
    {
        return $this->generate->js() . PHP_EOL;
    }
}
