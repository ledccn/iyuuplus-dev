<?php

namespace app\model;

use plugin\admin\app\model\Base;

/**
 * 动态令牌
 * @property integer $id ID(主键)
 * @property string $name 名称
 * @property string $secret 密钥
 * @property string $issuer 发行方
 * @property integer $t0 开始纪元
 * @property integer $t1 时间间隔
 * @property string $created_at 创建时间
 */
class Totp extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cn_totp';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
