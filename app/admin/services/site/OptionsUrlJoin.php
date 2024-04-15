<?php

namespace app\admin\services\site;

use Ledc\Element\Decorator;

/**
 * 下载种子URL附加参数规则
 */
class OptionsUrlJoin extends Decorator
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
                <label class="layui-form-label">附加参数</label>
                <div class="layui-input-block">
                <input type="checkbox" name="options[url_join][https]" title="https=1" value="1" lay-skin="primary" lay-filter="url_join-checkbox-filter">
                <input type="checkbox" name="options[url_join][ipv4]" title="ipv4=1" value="1" lay-skin="primary" lay-filter="url_join-checkbox-filter"> 
                <input type="checkbox" name="options[url_join][ipv6]" title="ipv6=1" value="1" lay-skin="primary" lay-filter="url_join-checkbox-filter">
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
        return $this->generate->js() . PHP_EOL . file_get_contents(__DIR__ . '/nexusphp_url_join.js');
    }
}
