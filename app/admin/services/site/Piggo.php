<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * piggo 站点表单：存取令牌（无 Cookie / Passkey / 附加参数）
 */
class Piggo extends Decorator
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
                <label class="layui-form-label required">存取令牌<i class="layui-icon layui-icon-help" lay-on="help_piggo_token"></i></label>
                <div class="layui-input-block">
                    <input type="text" name="options[token]" value="" required lay-verify="required" placeholder="请输入存取令牌" class="layui-input" lay-affix="eye">
                </div>
            </div>

EOF;
        return $html;
    }

    /**
     * 输出JavaScript
     * @return string
     */
    public function js(): string
    {
        $js = <<<EOF
        (function () {
            layui.$("#cookie_required_label").closest(".layui-form-item").hide();
            layui.$("#cookie_required_input").prop("required", false).removeAttr("lay-verify");
            layui.util.on("lay-on", {
                help_piggo_token: function () {
                    layui.layer.confirm("获取方法：个人中心 - 安全设定 - 存取令牌，<br>【建立存取令牌】后复制到此处", {
                        btn: ["前往获取", "确定"]
                    }, function () {
                        window.open("https://piggo.me/usercp?tab=security");
                    });
                }
            });
        })();
EOF;

        return $this->generate->js() . PHP_EOL . $js;
    }
}
