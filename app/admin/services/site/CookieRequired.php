<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * 用户Cookies
 */
class CookieRequired extends Decorator
{
    /**
     * 输出HTML
     * @return string
     */
    public function html(): string
    {
        return $this->generate->html();
    }

    /**
     * 输出输出JavaScript
     * @return string
     */
    public function js(): string
    {
        return $this->generate->js() . PHP_EOL . <<<EOL
            layui.$("#cookie_required_label").addClass("required");
            layui.$("#cookie_required_input").attr("required", true);
            layui.$("#cookie_required_input").attr("lay-verify", "required");
EOL;
    }
}
