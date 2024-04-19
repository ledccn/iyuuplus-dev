<?php

namespace app\model;

use plugin\admin\app\model\Base;

/**
 * 自动转移
 * @property integer $transfer_id 主键(主键)
 * @property integer $from_client_id 来源
 * @property integer $to_client_id 目标
 * @property string $info_hash 种子infohash
 * @property string $directory 转换前目录
 * @property string $convert_directory 转换后目录
 * @property string $torrent_file 种子文件路径
 * @property string $message 结果消息
 * @property integer $state 状态
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
}
