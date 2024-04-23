<?php

namespace app\admin\controller;

use app\common\HasBackupRecovery;
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
    use HasDelete, HasBackupRecovery;

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

    /**
     * 格式化下拉列表
     * @param $items
     * @return Response
     */
    protected function formatSelect($items): Response
    {
        $value = \request()->input('value', 'folder_id');
        if (!in_array($value, ['folder_id', 'folder_value'], true)) {
            return $this->fail('非法value参数');
        }

        $formatted_items = [];
        /** @var Folder $item */
        foreach ($items as $item) {
            $formatted_items[] = [
                'name' => $item->folder_alias,
                'value' => $item->{$value}
            ];
        }
        return $this->success('ok', $formatted_items);
    }
}
