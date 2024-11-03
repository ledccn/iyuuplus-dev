<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\Dict;
use plugin\admin\app\model\Option;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;

/**
 * 字典管理 
 */
class DictController extends Base
{
    /**
     * 不需要授权的方法
     */
    protected $noNeedAuth = ['get'];

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('dict/index');
    }

    /**
     * 查询
     * @param Request $request
     * @return Response
     */
    public function select(Request $request): Response
    {
        $name = $request->get('name', '');
        if ($name && is_string($name)) {
            $items = Option::where('name', 'like', "dict_$name%")->get()->toArray();
        } else {
            $items = Option::where('name', 'like', 'dict_%')->get()->toArray();
        }
        foreach ($items as &$item) {
            $item['name'] = Dict::optionNameTodictName($item['name']);
        }
        return $this->json(0, 'ok', $items);
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $name = $request->post('name');
            if (Dict::get($name)) {
                return $this->json(1, '字典已经存在');
            }
            $values = (array)$request->post('value', []);
            Dict::save($name, $values);
        }
        return raw_view('dict/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $name = $request->post('name');
            if (!Dict::get($name)) {
                return $this->json(1, '字典不存在');
            }
            Dict::save($name, $request->post('value'));
        }
        return raw_view('dict/update');
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $names = (array)$request->post('name');
        Dict::delete($names);
        return $this->json(0);
    }

    /**
     * 获取
     * @param Request $request
     * @param $name
     * @return Response
     */
    public function get(Request $request, $name): Response
    {
        return $this->json(0, 'ok', Dict::get($name));
    }

}
