<?php

namespace app\common;

use Illuminate\Database\Eloquent\Collection;
use Iyuu\SiteManager\Contracts\RecoveryInterface;
use support\exception\BusinessException;
use support\Request;
use support\Response;

/**
 * 数据备份与恢复
 */
trait HasBackupRecovery
{
    /**
     * 数据备份
     * @param Request $request
     * @return Response
     */
    public function backup(Request $request): Response
    {
        /** @var Collection $list */
        $list = ($this->model)::get();
        if ($list->isNotEmpty()) {
            $date = date('YmdHis');
            $dir = runtime_path('backup');
            $name = $this->model->getTable() . $date . '.json';
            $filename = $dir . DIRECTORY_SEPARATOR . $name;
            file_put_contents($filename, json_encode($list->toArray(), JSON_UNESCAPED_UNICODE));
            return \response()->download($filename, $name);
        }

        return $this->fail('数据为空，无需备份');
    }

    /**
     * 数据恢复
     * @param Request $request
     * @return Response
     */
    public function recovery(Request $request): Response
    {
        $file = $request->file('file');
        if ($this->model instanceof RecoveryInterface) {
            $list = json_decode(file_get_contents($file->getPathname()), true);
            $this->model->recoveryHandle($list);
            return $this->success('恢复成功：' . ($this->model)::count());
        }

        return $this->fail('未实现数据恢复接口，已忽略类：' . get_class($this->model));
    }
}
