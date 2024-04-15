<?php

namespace app\model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use plugin\admin\app\model\Base;

/**
 * 下载器
 * @property int $id 主键
 * @property string $brand 下载器品牌
 * @property string $title 标题
 * @property string $hostname 协议主机
 * @property string $endpoint 接入点
 * @property string $username 用户名
 * @property string $password 密码
 * @property string $watch_path 监控目录
 * @property string $save_path 资源保存路径
 * @property string $torrent_path 种子目录
 * @property int $root_folder 创建多文件子目录
 * @property int $is_debug 调试
 * @property int $is_default 默认
 * @property int $enabled 启用
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Client extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cn_client';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 获取默认下载器
     * @return self|Builder|null
     */
    public static function getDefaultClient(): self|Builder|null
    {
        return static::where('is_default', 1)->where('enabled', 1)->first();
    }

    /**
     * 取消默认
     * @param Client $model
     * @return void
     */
    public static function cancelDefault(self $model): void
    {
        /** @var Collection $list */
        $list = Client::where('is_default', '=', 1)->where('id', '<>', $model->id)->get();
        if (!$list->isEmpty()) {
            $list->each(function (Client $client) {
                $client->is_default = 0;
                $client->save();
            });
        }
    }

    /**
     * 备份到Json文件
     * @param Client $model
     * @return void
     */
    public static function backupToJson(self $model): void
    {
        /** @var Collection $list */
        $list = Client::get();
        if (!$list->isEmpty()) {
            file_put_contents(runtime_path('backup') . DIRECTORY_SEPARATOR . $model->getTable() . '.json', json_encode($list->toArray(), JSON_UNESCAPED_UNICODE));
        }
    }
}
