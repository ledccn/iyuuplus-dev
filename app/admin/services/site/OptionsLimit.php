<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * 下载种子限速规则
 */
class OptionsLimit extends Decorator
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
                <label class="layui-form-label required">辅种数量</label>
                <div class="layui-input-group">
                    <input type="number" name="options[limit][count]" value="20" required lay-verify="required" placeholder="每天辅种的总数量" class="layui-input">
                    <div class="layui-input-suffix">个/天<span class="layui-badge">擅自调整加大，可能导致站点账号异常</span></div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label required">辅种间隔</label>
                <div class="layui-input-group">
                    <input type="number" name="options[limit][sleep]" value="5" required lay-verify="required" placeholder="每个种子间隔时间" class="layui-input">
                    <div class="layui-input-suffix">秒</div>
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
