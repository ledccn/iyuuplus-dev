<?php

namespace app\model;

use app\admin\services\SitesServices;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Collection;
use Iyuu\SiteManager\Contracts\Config;
use Iyuu\SiteManager\Contracts\RecoveryInterface;
use plugin\admin\app\model\Base;

/**
 * 站点配置
 * @property integer $id 主键
 * @property int $sid 站点ID
 * @property string $site 站点名称
 * @property string $nickname 昵称
 * @property string $base_url 域名
 * @property string $mirror 镜像域名
 * @property string $cookie cookie
 * @property bool|int $cookie_required 必须cookie下载种子
 * @property string $download_page 下载种子页
 * @property string $details_page 详情页
 * @property string $reseed_check 检查项
 * @property string $bind_check 绑定字段
 * @property mixed $options 用户配置值
 * @property integer $disabled 禁用
 * @property integer $is_https 可选：0http，1https，2http+https
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Site extends Base implements RecoveryInterface
{
    /**
     * 数据表名称
     */
    public const string TABLE_NAME = 'cn_sites';
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = self::TABLE_NAME;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 需要恢复的字段
     */
    private const RECOVERY_FIELD = ['mirror', 'cookie', 'options', 'disabled'];

    /**
     * 类型转换。
     * @var array
     */
    protected $casts = [
        'options' => AsArrayObject::class,
    ];

    /**
     * 数据恢复
     * @param array $list
     * @return bool
     */
    public function recoveryHandle(array $list): bool
    {
        SitesServices::sync();
        foreach ($list as $data) {
            if ($model = self::uniqueSid($data['sid'])) {
                foreach (self::RECOVERY_FIELD as $field) {
                    $model->{$field} = $data[$field];
                }
                $model->save();
            }
        }
        return true;
    }

    /**
     * 获取站点模型
     * @param string $site
     * @return Builder|static|null
     */
    public static function uniqueSite(string $site): Builder|static|null
    {
        return static::where('site', '=', $site)->first();
    }

    /**
     * 获取站点模型
     * @param int $sid
     * @return Builder|static|null
     */
    public static function uniqueSid(int $sid): Builder|static|null
    {
        return static::where('sid', '=', $sid)->first();
    }

    /**
     * 构造器：获取启用的站点
     * @return Builder
     */
    public static function getEnabled(): Builder
    {
        return static::where('disabled', '=', 0);
    }

    /**
     * 备份到Json文件
     * @param Site $model
     * @return void
     */
    public static function backupToJson(self $model): void
    {
        /** @var Collection $list */
        $list = Site::get();
        if (!$list->isEmpty()) {
            file_put_contents(Config::getFilename(), json_encode($list->toArray(), JSON_UNESCAPED_UNICODE));
        }
    }
}
