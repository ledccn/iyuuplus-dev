<?php

namespace app\admin\controller;

use app\common\HasDelete;
use app\model\Transfer;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use support\Request;
use support\Response;

/**
 * 自动转移
 */
class TransferController extends Crud
{
    use HasDelete;

    /**
     * @var Transfer
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Transfer;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('transfer/index');
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
        return view('transfer/insert');
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
        return view('transfer/update');
    }
}
