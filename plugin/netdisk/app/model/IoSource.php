<?php

namespace plugin\netdisk\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键(主键)
 * @property integer $admin_id 创建人ID
 * @property string $title 文件名
 * @property string $hash hash
 * @property string $ext 扩展名
 * @property integer $is_folder 是否是文件夹
 * @property integer $pid 父级ID
 * @property string $pids 所有父级
 * @property integer $file_id 附件ID
 * @property integer $file_size 文件大小
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $weight 排序号
 * @property integer $status 状态
 */
class IoSource extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'io_source';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 根据ID获取目录与父级（含本身）
     * @param $id
     * @return array
     */
    public static function getPathInfo($id){
        if($id==0){
            return [[],[]];
        }
        $pids = [];
        $info = self::where('id',$id)->select('id','title','pid','pids')->first()->toArray();
        if($info['pids']){
            $pids = explode(',',$info['pids']);
            $pids = array_merge($pids,[$id]);
        }else{
            $pids = [$id];
        }
        $list = self::whereIn('id',$pids)->pluck('title')->toArray();
        return [$list,$pids];
    }
    
}
