<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * colapt（可乐PT）站点表单：开放 API 的 API Key（X-API-Key 请求头，无 Cookie / Passkey）
 */
class Colapt extends Decorator
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
                <label class="layui-form-label required">API Key<i class="layui-icon layui-icon-help" lay-on="help_colapt_api_key"></i></label>
                <div class="layui-input-block">
                    <input type="text" name="options[x_api_key]" value="" required lay-verify="required" placeholder="请输入 API Key" class="layui-input" lay-affix="eye">
                    <div class="layui-form-mid layui-text-em">填可乐PT『账号设置 → API Key』里新建的 Key，范围勾选「读取」和「下载」</div>
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
                help_colapt_api_key: function () {
                    layui.layer.confirm("获取方法：账号设置 - API Key，<br>新建 Key 时范围勾选【读取】和【下载】，复制到此处", {
                        btn: ["查看接口文档", "确定"]
                    }, function () {
                        window.open("https://www.colapt.com/api-docs");
                    });
                }
            });
        })();
EOF;

        return $this->generate->js() . PHP_EOL . $js;
    }
}
