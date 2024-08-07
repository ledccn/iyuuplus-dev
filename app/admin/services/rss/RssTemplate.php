<?php

namespace app\admin\services\rss;

use app\command\RssCommand;
use app\model\enums\DownloaderMarkerEnums;
use Ledc\Element\GenerateInterface;
use plugin\cron\app\interfaces\CrontabAbstract;
use plugin\cron\app\services\CrontabRocket;
use Workerman\Crontab\Crontab as WorkermanCrontab;

/**
 * RSS订阅，计划任务配置模板
 */
class RssTemplate extends CrontabAbstract
{
    /**
     * @return array
     */
    public static function select(): array
    {
        return RssSelectEnums::select();
    }

    /**
     * @param int $type
     * @return GenerateInterface|null
     */
    public function generate(int $type): ?GenerateInterface
    {
        return match ($type) {
            RssSelectEnums::rss->value => $this,
            default => null
        };
    }

    /**
     * 启动器
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab|null
     */
    public function start(CrontabRocket $rocket): ?WorkermanCrontab
    {
        return static::startCrontab(RssSelectEnums::rss->value, 'RSS订阅', $rocket);
    }

    /**
     * @return string
     */
    public function html(): string
    {
        $command = RssCommand::COMMAND_NAME;
        $markerEmpty = DownloaderMarkerEnums::Empty->value;
        $markerTag = DownloaderMarkerEnums::Tag->value;
        $markerCategory = DownloaderMarkerEnums::Category->value;
        return PHP_EOL . <<<EOF
<script src="/parameter.js"></script>
<div class="layui-form-item layui-hide">
    <label class="layui-form-label required">命令名称</label>
    <div class="layui-input-block">
        <input type="text" name="target" value="$command" required lay-verify="required" placeholder="请输入命令名称" class="layui-input" readonly>
    </div>
</div>

<div name="parameter" id="parameter" value="" class="layui-hide"></div>

<div class="layui-form-item">
    <label class="layui-form-label required">RSS地址</label>
    <div class="layui-input-block">
        <input type="text" name="parameter[rss_url]" value="" required lay-verify="required" placeholder=""
               class="layui-input">
    </div>
</div>

<div class="layui-form-item">
    <label class="layui-form-label required">下载器</label>
    <div class="layui-input-block">
        <div name="parameter[client_id]" id="client_id" value=""></div>
    </div>
</div>

<div class="layui-form-item">
    <label class="layui-form-label required" title="添加下载任务时，对种子做标记">标记规则</label>
    <div class="layui-input-block">
        <input type="radio" name="parameter[marker]" value="$markerEmpty" title="不操作" checked>
        <input type="radio" name="parameter[marker]" value="$markerTag" title="标记标签">
        <input type="radio" name="parameter[marker]" value="$markerCategory" title="标记分类">
    </div>
    <div class="layui-form-mid layui-text-em">添加下载任务时，对种子做标记（需要下载器支持）</div>
</div>

<div class="layui-form-item">
    <label class="layui-form-label">保存路径</label>
    <div class="layui-input-block">
        <input type="text" name="parameter[save_path]" value="" placeholder="如果留空，依次使用：资源文件夹 -> 下载器默认" autocomplete="off" class="layui-input">
    </div>
</div>

<div class="layui-form-item">
    <div class="layui-inline">
      <label class="layui-form-label">种子大小</label>
      <div class="layui-input-inline">
        <input type="number" name="parameter[size_min]" placeholder="最小值" autocomplete="off" class="layui-input">
      </div>
      <div class="layui-input-inline" style="width: 100px;" title="最小值单位">
        <select name="parameter[size_min_unit]">
          <option value="KB">KB</option>
          <option value="MB">MB</option>
          <option value="GB" selected>GB</option>
          <option value="TB">TB</option>
        </select>
      </div>
      <div class="layui-form-mid">-</div>
      <div class="layui-input-inline">
        <input type="number" name="parameter[size_max]" placeholder="最大值" autocomplete="off" class="layui-input">
      </div>
      <div class="layui-input-inline" style="width: 100px;" title="最大值单位">
        <select name="parameter[size_max_unit]">
          <option value="KB">KB</option>
          <option value="MB">MB</option>
          <option value="GB" selected>GB</option>
          <option value="TB">TB</option>
        </select>
      </div>
    </div>
</div>

<div class="layui-form-item">
    <label class="layui-form-label">匹配标题</label>
    <div class="layui-input-block">
        <div class="layui-collapse" lay-accordion>
          <div class="layui-colla-item">
            <div class="layui-colla-title">简易模式</div>
            <div class="layui-colla-content layui-show">
                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">包含关键字</label>
                        <div class="layui-input-inline" style="width: 400px;">
                            <div name="parameter[text_selector]" id="text_selector" value=""></div>
                        </div>
                    </div>
                    <div class="layui-inline">
                        <div class="layui-input-inline" style="width: 100px;" title="包含的逻辑关系">
                            <select name="parameter[text_selector_op]">
                                <option value="or" selected>逻辑或</option>
                                <option value="and">逻辑与</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">排除关键字</label>
                        <div class="layui-input-inline" style="width: 400px;">
                            <div name="parameter[text_filter]" id="text_filter" value=""></div>
                        </div>
                    </div>
                    <div class="layui-inline">
                        <div class="layui-input-inline" style="width: 100px;" title="排除的逻辑关系">
                            <select name="parameter[text_filter_op]">
                                <option value="or" selected>逻辑或</option>
                                <option value="and">逻辑与</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
          </div>
          <div class="layui-colla-item">
            <div class="layui-colla-title">正则模式</div>
            <div class="layui-colla-content">
                <div class="layui-form-item">
                    <label class="layui-form-label">选中规则</label>
                    <div class="layui-input-block">
                        <input type="text" name="parameter[regex_selector]" value="" placeholder="请输入正则的表达式" autocomplete="off" class="layui-input">
                        <div class="layui-form-mid layui-text-em">正则表达式包含三个部分：分隔符、表达式、修饰符；您只需填写分隔符、表达式</div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">排除规则</label>
                    <div class="layui-input-block">
                        <input type="text" name="parameter[regex_filter]" value="" placeholder="请输入正则的表达式" autocomplete="off" class="layui-input">
                        <div class="layui-form-mid layui-text-em">正则表达式包含三个部分：分隔符、表达式、修饰符；您只需填写分隔符、表达式</div>
                    </div>
                </div>
            </div>
          </div>
        </div>
        <div class="layui-form-mid layui-text-em">规则优先级：<span class="layui-badge layui-bg-gray">简易模式</span><i class="layui-icon layui-icon-right"></i><span class="layui-badge layui-bg-gray">正则模式</span></div>
    </div>
</div>

EOF;
    }

    /**
     * @return string
     */
    public function js(): string
    {
        return PHP_EOL . file_get_contents(__DIR__ . '/rss.js');
    }
}
