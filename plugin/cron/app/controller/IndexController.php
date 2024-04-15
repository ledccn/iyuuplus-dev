<?php

namespace plugin\cron\app\controller;

use plugin\admin\app\controller\Base;
use plugin\cron\app\services\generates\LayuiTemplate;
use support\Container;
use support\Request;
use support\Response;

/**
 * 插件默认控制器
 */
class IndexController extends Base
{
    /**
     * 插件默认首页
     * @return Response
     */
    public function index(): Response
    {
        return view('index/index', ['name' => 'crontab']);
    }

    /**
     * 计划任务类型
     * @param Request $request
     * @return Response
     */
    public function taskType(Request $request): Response
    {
        /** @var LayuiTemplate $layuiTemplate */
        $layuiTemplate = Container::get(LayuiTemplate::class);
        return $this->success('ok', $this->formatSelectEnum($layuiTemplate->select()));
    }

    /**
     * 格式化下拉列表
     * @param array $items
     * @return array
     */
    private function formatSelectEnum(array $items): array
    {
        $formatted_items = [];
        foreach ($items as $name => $value) {
            $formatted_items[] = [
                'name' => $name,
                'value' => $value
            ];
        }
        return $formatted_items;
    }
}
