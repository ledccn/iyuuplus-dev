<?php

namespace plugin\cron\app\services\generates;

use Ledc\Element\Decorator;

/**
 * shell脚本
 */
class GenerateShellExec extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label required">脚本内容</label>
                <div class="layui-input-block">
                    <textarea type="text" name="target" value="" required lay-verify="required" placeholder="请输入脚本内容，可以直接粘贴执行方式和可执行文件路径" class="layui-textarea"></textarea>
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
