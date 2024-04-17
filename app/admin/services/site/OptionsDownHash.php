<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * 用户downhash
 */
class OptionsDownHash extends Decorator
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
                <label class="layui-form-label required">downhash</label>
                <div class="layui-input-block">
                    <input type="text" name="options[downhash]" value="" required lay-verify="required" placeholder="请输入downhash" class="layui-input" lay-affix="eye">
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
