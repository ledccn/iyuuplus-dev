<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * RSS的URL
 */
class OptionsRssUrl extends Decorator
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
                <label class="layui-form-label">RSS链接</label>
                <div class="layui-input-block">
                    <input type="text" name="options[rss_url]" value="" placeholder="请输入RSS链接" class="layui-input">
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
