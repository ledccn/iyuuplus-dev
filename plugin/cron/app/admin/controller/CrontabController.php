<?php

namespace plugin\cron\app\admin\controller;

use Ledc\Element\GenerateInterface;
use plugin\admin\app\controller\Crud;
use plugin\cron\app\model\Crontab;
use plugin\cron\app\model\CrontabObserver;
use plugin\cron\app\services\CrontabEventEnums;
use plugin\cron\app\services\generates\LayuiTemplate;
use support\Container;
use support\exception\BusinessException;
use support\Request;
use support\Response;

/**
 * 计划任务
 */
class CrontabController extends Crud
{
    /**
     * @var Crontab
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Crontab;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('crontab/index');
    }

    /**
     * 查询模型
     * @param Request $request
     * @return Crontab
     * @throws BusinessException
     */
    private function findByPk(Request $request): Crontab
    {
        $primary_key = $this->model->getKeyName();
        $id = $request->get($primary_key);
        $model = $this->model->find($id);
        if (!$model) {
            throw new BusinessException('记录不存在', 2);
        }
        return $model;
    }

    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function find(Request $request): Response
    {
        $model = $this->findByPk($request);

        return $this->success('ok', $model->toArray());
    }

    /**
     * 获取Layui计划任务配置模板
     * @param int $type
     * @return GenerateInterface
     * @throws BusinessException
     */
    private function getLayuiTemplate(int $type): GenerateInterface
    {
        /** @var LayuiTemplate $layuiTemplate */
        $layuiTemplate = Container::get(LayuiTemplate::class);
        return $layuiTemplate->generate($type);
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::insert($request);
        }
        $task_type = $request->get('task_type', 0);
        $form = $this->getLayuiTemplate($task_type);
        return view('crontab/save', [
            'html' => $form->html(),
            'js' => $form->js()
        ]);
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::update($request);
        }
        $task_type = $request->get('task_type', 0);
        $form = $this->getLayuiTemplate($task_type);
        return view('crontab/save', [
            'html' => $form->html(),
            'js' => $form->js()
        ]);
    }

    /**
     * 手动运行
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function start(Request $request): Response
    {
        $model = $this->findByPk($request);
        if (!$model->enabled) {
            return $this->fail('请先启用任务后运行');
        }
        CrontabObserver::saveEventToDir($model->crontab_id, CrontabEventEnums::start->name);
        return $this->success();
    }

    /**
     * 手动停止
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function stop(Request $request): Response
    {
        $model = $this->findByPk($request);
        if (!$model->enabled) {
            return $this->success('任务已关闭，无需停止');
        }
        CrontabObserver::saveEventToDir($model->crontab_id, CrontabEventEnums::stop->name);
        return $this->success();
    }

    /**
     * 显示终端输出
     * @param Request $request
     * @return Response
     */
    public function screen(Request $request): Response
    {
        return view('crontab/screen', [
            'app_key' => config('plugin.webman.push.app.app_key'),
            'websocket_port' => parse_url(config('plugin.webman.push.app.websocket'), PHP_URL_PORT),
        ]);
    }

    /**
     * 删除
     * - 会触发模型事件
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function delete(Request $request): Response
    {
        $count = 0;
        if ($ids = $this->deleteInput($request)) {
            $count = $this->model->destroy($ids);
        }
        return $this->success('ok', ['count' => $count]);
    }

    /**
     * 对用户输入表单过滤
     * @param array $data
     * @return array
     * @throws BusinessException
     */
    protected function inputFilter(array $data): array
    {
        $table = config('plugin.admin.database.connections.mysql.prefix') . $this->model->getTable();
        $allow_column = $this->model->getConnection()->select("desc `$table`");
        if (!$allow_column) {
            throw new BusinessException('表不存在', 2);
        }
        $columns = array_column($allow_column, 'Type', 'Field');
        foreach ($data as $col => $item) {
            if (!isset($columns[$col])) {
                unset($data[$col]);
                continue;
            }
            // 非字符串类型传空则为null
            if ($item === '' && !str_contains(strtolower($columns[$col]), 'varchar') && !str_contains(strtolower($columns[$col]), 'text')) {
                $data[$col] = null;
            }
        }
        if (empty($data['created_at'])) {
            unset($data['created_at']);
        }
        if (empty($data['updated_at'])) {
            unset($data['updated_at']);
        }
        return $data;
    }
}
