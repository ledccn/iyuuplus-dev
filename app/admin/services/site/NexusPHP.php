<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * NexusPHP
 */
class NexusPHP extends Decorator
{
    /**
     * @return string
     */
    public function html(): string
    {
        $html = $this->generate->html();
        $html .= <<<EOF

            <div class="layui-form-item">
                <label class="layui-form-label required">Passkey</label>
                <div class="layui-input-block">
                    <input type="text" name="options[passkey]" value="" required lay-verify="required" placeholder="请输入密钥passkey" class="layui-input" lay-affix="eye">
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
