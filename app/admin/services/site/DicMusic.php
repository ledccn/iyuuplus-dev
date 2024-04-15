<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * dicmusic
 */
class DicMusic extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label required">torrent_pass</label>
                <div class="layui-input-block">
                    <input type="text" name="options[torrent_pass]" value="" required lay-verify="required" placeholder="请输入torrent_pass" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label required">authkey</label>
                <div class="layui-input-block">
                    <input type="text" name="options[authkey]" value="" required lay-verify="required" placeholder="请输入authkey" class="layui-input">
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
