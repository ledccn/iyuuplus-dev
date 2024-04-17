<?php

namespace app\admin\controller;

use app\common\HasDelete;
use app\model\Folder;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use support\Request;
use support\Response;

/**
 * 数据目录
 */
class FolderController extends Crud
{
    use HasDelete;

    /**
     * @var Folder
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Folder;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('folder/index');
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
        return view('folder/save');
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
        return view('folder/save');
    }
}
