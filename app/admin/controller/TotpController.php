<?php

namespace app\admin\controller;

use app\common\HasBackupRecovery;
use app\common\HasDelete;
use app\model\Totp;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use support\Request;
use support\Response;

/**
 * 动态令牌
 */
class TotpController extends Crud
{
    use HasDelete, HasBackupRecovery;

    /**
     * @var Totp
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Totp;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('totp/index');
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
        return view('totp/insert');
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
        return view('totp/update');
    }
}
