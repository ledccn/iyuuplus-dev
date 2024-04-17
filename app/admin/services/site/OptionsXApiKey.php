<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * 用户存取token
 */
class OptionsXApiKey extends Decorator
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
                <label class="layui-form-label required">存取令牌<i class="layui-icon layui-icon-help" lay-on="help_x_api_key"></i></label>
                <div class="layui-input-block">
                    <input type="text" name="options[x_api_key]" value="" required lay-verify="required" placeholder="请输入存取令牌" class="layui-input" lay-affix="eye">
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
        $js = <<<EOF
        layui.util.on('lay-on', {
            help_x_api_key: function () {
                layui.layer.confirm('获取方法：控制台 - 實驗室 - 存取令牌，<br>【建立存取令牌】后复制到此处', {
                    btn: ['前往获取', '确定']
                }, function(){
                    window.open('https://kp.m-team.cc/usercp?tab=laboratory')
                });
            },
        });
EOF;

        return $this->generate->js() . PHP_EOL . $js;
    }
}
