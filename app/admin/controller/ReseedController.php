<?php

namespace app\admin\controller;

use app\common\HasClear;
use app\common\HasDelete;
use app\model\enums\ReseedStatusEnums;
use app\model\Reseed;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use support\Request;
use support\Response;

/**
 * 自动辅种
 */
class ReseedController extends Crud
{
    use HasDelete, HasClear;

    /**
     * @var Reseed
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Reseed;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('reseed/index');
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
        return view('reseed/insert');
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
        return view('reseed/update');
    }

    /**
     * 刷新辅种
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function refresh(Request $request): Response
    {
        $request->canOnlyPost();
        if (!Reseed::getStatusEqFail()->count()) {
            return $this->fail('没有失败的种子，无需刷新');
        }

        if ($ids = $this->deleteInput($request)) {
            $primary_key = $this->model->getKeyName();
            $builder = Reseed::getStatusEqFail()->whereIn($primary_key, $ids);
        } else {
            $builder = Reseed::getStatusEqFail();
        }
        $count = $builder->update(['status' => ReseedStatusEnums::Default->value]);

        return $this->success('ok', ['count' => $count]);
    }
}
