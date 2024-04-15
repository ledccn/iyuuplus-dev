<?php

namespace plugin\cron\app\services\generates;

use Ledc\Element\Decorator;

/**
 * 执行类方法
 */
class GenerateClassMethod extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label required">类方法名</label>
                <div class="layui-input-block">
                    <input type="text" name="target" value="" required lay-verify="required" placeholder="请输入任务名和方法名，例：class@method" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label" title="使用call_user_func_array调用回调函数，并把一个数组参数作为回调函数的参数">数组参数</label>
                <div class="layui-input-block">
                    <textarea name="parameter" value="" placeholder="请输入传入回调函数的数组（json对象字符串）；使用call_user_func_array调用回调函数，并把一个数组参数作为回调函数的参数" class="layui-textarea"></textarea>
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
