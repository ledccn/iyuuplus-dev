<?php

namespace app\controller;

use support\Request;
use support\Response;

/**
 * 默认控制器
 */
class IndexController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return redirect('/app/admin');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function view(Request $request): Response
    {
        return view('index/view', ['name' => 'webman']);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function json(Request $request): Response
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
