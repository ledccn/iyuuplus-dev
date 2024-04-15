<?php

namespace plugin\cron\app\services\generates;

use Ledc\Element\Decorator;

/**
 * 访问URL
 */
class GenerateUrlRequest extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label required">URL地址</label>
                <div class="layui-input-block">
                    <input type="text" name="target" value="" required lay-verify="required|url" placeholder="请输入要访问的URL" class="layui-input">
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
