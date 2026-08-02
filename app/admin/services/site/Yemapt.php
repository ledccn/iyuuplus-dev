<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * 野马PT开放API配置
 */
class Yemapt extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        return $this->generate->html() . <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label required">AuthKey</label>
                <div class="layui-input-block">
                    <input type="text" name="options[authkey]" value="" required lay-verify="required" placeholder="请在野马PT个人详情页创建并填写API AuthKey" class="layui-input" lay-affix="eye">
                </div>
            </div>

EOF;
    }

    /**
     * 隐藏野马PT不再使用的Cookie配置项
     */
    public function js(): string
    {
        return $this->generate->js() . PHP_EOL . <<<'EOF'
            layui.$("#cookie_required_label").closest(".layui-form-item").remove();
EOF;
    }
}
