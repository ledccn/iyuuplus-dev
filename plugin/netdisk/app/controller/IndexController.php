<?php

namespace plugin\netdisk\app\controller;

use plugin\netdisk\app\common\Sort;
use plugin\netdisk\app\model\IoShare;
use plugin\netdisk\app\model\IoSource;
use support\Request;
use support\Response;

class IndexController
{

    public function index()
    {
        return view('index/index', ['name' => 'netdisk']);
    }

    public function share(Request $request, $hash): Response
    {

        $share = IoShare::where('share_hash', $hash)->where('status', 1)->first();
        if (!$share) {
            return response('not found,code -1', 404);
//            return response('未找到分享文件',404);
        }
        $share = $share->toArray();
        if (!is_null($share['expire_at']) && $share['expire_at'] < date('Y-m-d H:i:s')) {
            //过期自动清除
            IoShare::where('share_hash', $hash)->update(['status' => 99]);
            return response('not found,code -3', 404);
        }
        IoShare::where('id', $share['id'])->increment('view_num');

        $show_status = 1;//1无密码 2有密码 3密码错误 4密码正确
        if ($share['share_password']) {
            $show_status = 2;
            if (session("netdisk_{$share['id']}")) {
                $show_status = 4;
            } else {
                if ($request->method() === 'POST') {
                    if ($request->post('file_password') !== null) {
                        if ($share['share_password'] != $request->post('file_password')) {
                            $show_status = 3;
                        } else {
                            $show_status = 4;
                            $request->session()->set("netdisk_{$share['id']}", true);
                        }
                    }
                }
            }
        }

        $pid = $share['source_id'];
        $fname = $request->get('fname', '');

        $dir_arr = [];
        if ($fname) {
            $info = IoSource::where('hash', $fname)->first();

            if (!$info) {
                return response('not found,code -2', 404);
            }
            $info = $info->toArray();
            if ($info['is_folder'] == 1) {
                //文件夹
                $pid = $info['id'];
                $pids = $info['pids'] . ',' . $info['id'];
                $pid_arr = explode(',', $pids);
                $index = array_search($share['source_id'], $pid_arr);//将分享的目录查找出来
                $pid_arr_new = array_slice($pid_arr, $index + 1);//将分享的目录及之前的目录全部剔除，避免暴露非分享目录
                $dir_arr = IoSource::whereIn('id', $pid_arr_new)->pluck('title', 'hash')->toArray();
            } else {
                //文件

                [$path_arr, $pids] = IoSource::getPathInfo($info['pid']);
                $paths = implode('/', $path_arr);

                $path = $paths;
                $base_dir = config('plugin.netdisk.io.base_dir');
                $full_dir = $base_dir . md5($share['admin_id']) . '/' . $path . '/' . $info['title'];

                $isdown = $request->get('isdown', 0);
                if (isset($isdown) && $isdown == 1) {
                    //下载
                    IoShare::where('id', $share['id'])->increment('download_num');
                    return response()->download($full_dir, $info['title']);
                } else {
                    return response()->file($full_dir);
                }
            }

        }

        $list = [];
        if (in_array($show_status, [1, 4])) {
            if ($share['is_folder'] != 1) {
                //分享单文件直接下载
                $info = IoSource::where('id', $share['source_id'])->first();
                if (!$info) {
                    return response('not found,code -2', 404);
                }
                $info = $info->toArray();
                [$path_arr, $pids] = IoSource::getPathInfo($info['pid']);
                $paths = implode('/', $path_arr);
                $path = $paths;
                $base_dir = config('plugin.netdisk.io.base_dir');
                $full_dir = $base_dir . md5($share['admin_id']) . '/' . $path . '/' . $info['title'];
                //下载
                IoShare::where('id', $share['id'])->increment('download_num');
                return response()->download($full_dir, $info['title']);

            }
            $list = IoSource::where('pid', $pid)->where('status', 1)->get()->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['sort'] = $v['is_folder'] != 1 ? 1 : 0;//文件夹优先排序
            }

            //自然排序
            $list = Sort::arraySort($list, 'sort', false, 'title');
        }
        return view('index/share', [
            'share' => $share,
            'show_status' => $show_status,
            'list' => $list,
            'dir_arr' => json_encode($dir_arr),
        ]);

    }


}
