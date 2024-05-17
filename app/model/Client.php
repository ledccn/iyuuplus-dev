<?php

namespace app\model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Iyuu\BittorrentClient\ClientEnums;
use Iyuu\SiteManager\Contracts\RecoveryInterface;
use plugin\admin\app\common\Util;
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
 * @property string $watch_path 监控文件夹
 * @property string $save_path 资源文件夹
 * @property string $torrent_path 种子文件夹
 * @property int $root_folder 创建多文件子目录
 * @property int $is_debug 调试
 * @property int $is_default 默认
 * @property int $seeding_after_completed 校验后做种（辅种的种子在校验完成后自动做种）
 * @property int $enabled 启用
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Client extends Base implements RecoveryInterface
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
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * 数据恢复
     * @param array $list
     * @return bool
     */
    public function recoveryHandle(array $list): bool
    {
        foreach ($list as $data) {
            unset($data[$this->getKeyName()]);
            Util::db()->table($this->getTable())->insert($data);
        }
        return true;
    }

    /**
     * 获取下载器品牌枚举类
     * @return ClientEnums
     */
    public function getClientEnums(): ClientEnums
    {
        return ClientEnums::from($this->getAttribute('brand'));
    }

    /**
     * 构造器：获取启用的下载器
     * @return Builder
     */
    public static function getEnabled(): Builder
    {
        return static::where('enabled', '=', 1);
    }

    /**
     * 获取 启用&校验后做种 的下载器
     * @return Builder
     */
    public static function getEnabledSeedingAfterCompleted(): Builder
    {
        return static::where('enabled', '=', 1)->where('seeding_after_completed', 1);
    }

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
