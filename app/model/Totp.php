<?php

namespace app\model;

use OTPHP\OTPInterface;
use plugin\admin\app\model\Base;
use support\exception\BusinessException;
use Throwable;

/**
 * 动态令牌
 * @property integer $id ID(主键)
 * @property string $name 名称
 * @property string $secret 密钥
 * @property string $issuer 发行方
 * @property integer $t0 开始纪元
 * @property integer $t1 时间周期
 * @property string $algo 散列算法
 * @property integer $digits 令牌位数
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
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
     * 追加计算属性
     * @var string[]
     */
    protected $appends = ['auth_code'];

    /**
     * 计算属性访问器：动态密码
     * @return string
     * @throws BusinessException
     */
    protected function getAuthCodeAttribute(): string
    {
        $secret = $this->getAttribute('secret');
        $t0 = $this->getAttribute('t0');
        $period = $this->getAttribute('t1');
        $digest = $this->getAttribute('algo') ?? OTPInterface::DEFAULT_DIGEST;
        $digits = $this->getAttribute('digits') ?? OTPInterface::DEFAULT_DIGITS;

        try {
            $totp = \OTPHP\TOTP::create($secret, $period, $digest, $digits, $t0);
            return $totp->now();
        } catch (Throwable $throwable) {
            throw new BusinessException($throwable->getMessage());
        }
    }
}
