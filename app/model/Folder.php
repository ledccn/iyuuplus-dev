<?php

namespace app\model;

use plugin\admin\app\model\Base;

/**
 * 数据目录
 * @property integer $folder_id 主键(主键)
 * @property string $folder_alias 目录别名
 * @property string $folder_value 数据目录
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Folder extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cn_folder';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'folder_id';
}
