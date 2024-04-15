<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * zhuque
 */
class Zhuque extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF
            
            <div class="layui-form-item">
                <label class="layui-form-label">X-Csrf-Token</label>
                <div class="layui-input-block">
                    <input type="text" name="options[x_csrf_token]" value="" placeholder="请输入x-csrf-token" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label required">RSS Key</label>
                <div class="layui-input-block">
                    <input type="text" name="options[rss_key]" value="" required lay-verify="required" placeholder="请输入RSS Key" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label required">Torrent Key</label>
                <div class="layui-input-block">
                    <input type="text" name="options[torrent_key]" value="" required lay-verify="required" placeholder="请输入Torrent Key" class="layui-input">
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
