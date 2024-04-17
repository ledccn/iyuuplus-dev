<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * 高清城市cuhash
 */
class OptionsCuHashByHdcity extends Decorator
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
                <label class="layui-form-label required">cuhash<i class="layui-icon layui-icon-help" lay-on="help_cuhash"></i></label>
                <div class="layui-input-block">
                    <input type="text" name="options[cuhash]" value="" required lay-verify="required" placeholder="请输入cuhash" lay-affix="eye" class="layui-input">
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
            help_cuhash: function () {
                layui.layer.confirm('获取cuhash的方法：站点的种子列表页，在下载种子的图标上，点右键“复制链接”；<br>格式如：/download?id=56556&cuhash=这里就是cuhash', {
                    btn: ['前往获取', '确定']
                }, function(){
                    window.open('https://hdcity.city/pt')
                });
            },
        });
EOF;

        return $this->generate->js() . PHP_EOL . $js;
    }
}
