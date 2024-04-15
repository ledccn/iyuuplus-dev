<?php

namespace plugin\cron\app\services\generates;

use Ledc\Element\Decorator;

/**
 * webman命令
 */
class GenerateCommand extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label required">命令名称</label>
                <div class="layui-input-block">
                    <div name="target" id="target" value="" title="请输入webman命令名称"></div>
                    <div class="layui-form-mid layui-text-em"><strong id="description"></strong><pre id="usage"></pre></div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">调用参数</label>
                <div class="layui-input-block">
                    <input type="text" name="parameter" value="" placeholder="请输入webman命令的调用参数" class="layui-input">
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
        $all = webman_commands();
        $namespaces = json_encode($all['namespaces'], JSON_UNESCAPED_UNICODE);
        return $this->generate->js()
            . PHP_EOL . " const namespaces = $namespaces;"
            . PHP_EOL . file_get_contents(__DIR__ . '/commands.js') . PHP_EOL;
    }
}
