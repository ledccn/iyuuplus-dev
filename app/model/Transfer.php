<?php

namespace app\model;

use plugin\admin\app\model\Base;

/**
 * 自动转移
 * @property int $transfer_id 主键
 * @property int $from_client_id 来源下载器
 * @property int $to_client_id 目标下载器
 * @property string $info_hash 种子infohash
 * @property string $directory 转换前目录
 * @property string $convert_directory 转换后目录
 * @property string $torrent_file 种子文件路径
 * @property string $message 结果消息
 * @property int|bool $state 状态
 * @property int $last_time 最后操作时间
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Transfer extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cn_transfer';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'transfer_id';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * 依据client_id批量删除数据
     * @param int $from_client_id
     * @return int
     */
    public static function deleteByFromClientId(int $from_client_id): int
    {
        return self::deleteByColumnValue('from_client_id', $from_client_id);
    }

    /**
     * 依据client_id批量删除数据
     * @param int $to_client_id
     * @return int
     */
    public static function deleteByToClientId(int $to_client_id): int
    {
        return self::deleteByColumnValue('to_client_id', $to_client_id);
    }

    /**
     * 私有方法，批量删除数据
     * @param string $column
     * @param string $value
     * @return int
     */
    protected static function deleteByColumnValue(string $column, string $value): int
    {
        $count = 0;
        Transfer::where($column, '=', $value)->chunkById(50, function ($reseeds) use (&$count) {
            /** @var Reseed $reseed */
            foreach ($reseeds as $reseed) {
                $reseed->delete();
                $count++;
            }
        });
        return $count;
    }
}
