<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * 用户UID
 */
class OptionsUid extends Decorator
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
                <label class="layui-form-label required">用户UID</label>
                <div class="layui-input-group">
                    <input type="number" name="options[uid]" value="" required lay-verify="required" placeholder="请输入用户UID" class="layui-input">
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