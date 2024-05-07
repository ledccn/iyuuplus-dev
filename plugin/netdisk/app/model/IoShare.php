<?php

namespace plugin\netdisk\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键(主键)
 * @property string $title 分享名称
 * @property string $share_hash hash
 * @property integer $admin_id 创建人ID
 * @property integer $source_id 文档数据id
 * @property integer $is_folder 是否是文件夹
 * @property integer $file_id 附件ID
 * @property integer $share_password 访问密码,为空则无密码
 * @property integer expire_at 到期时间
 * @property integer view_num 预览次数
 * @property integer download_num 下载次数
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $weight 排序号
 * @property integer $status 状态
 */
class IoShare extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'io_share';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

}
