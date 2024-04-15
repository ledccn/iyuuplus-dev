<?php

namespace plugin\cron\app\admin\controller;

use plugin\admin\app\controller\Crud;
use plugin\cron\app\model\CrontabLog;
use support\exception\BusinessException;
use support\Request;
use support\Response;

/**
 * 日志
 */
class CrontabLogController extends Crud
{
    /**
     * @var CrontabLog
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new CrontabLog;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('crontab-log/index');
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
        return view('crontab-log/insert');
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
        return view('crontab-log/update');
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
}
