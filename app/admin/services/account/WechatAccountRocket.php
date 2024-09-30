<?php

namespace app\admin\services\account;

use ArrayAccess;
use Iyuu\SiteManager\Traits\HasConfig;

/**
 * 爱语飞飞微信扫码登录
 * @property integer $weid 主键(主键)
 * @property integer $uuid 用户id
 * @property string $nickname 昵称
 * @property string $identity 【计算属性】用户的身份凭据
 * @property string $token_password_hash 【计算属性】用户的token哈希值
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class WechatAccountRocket implements ArrayAccess
{
    use HasConfig;
}
