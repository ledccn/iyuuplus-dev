<?php

namespace plugin\cron\app\services\generates;

use Ledc\Element\Decorator;

/**
 * eval执行PHP代码
 */
class GenerateEvalCode extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label required">PHP代码</label>
                <div class="layui-input-block">
                    <textarea type="text" name="target" value="" required lay-verify="required" placeholder="请输入要执行的php代码字符串（eval函数：把字符串作为PHP代码执行）" class="layui-textarea"></textarea>
                    <div class="layui-form-mid layui-text-em"><a href="https://www.php.net/manual/zh/function.eval.php" target="_blank">PHP手册：eval函数</a></div>
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
