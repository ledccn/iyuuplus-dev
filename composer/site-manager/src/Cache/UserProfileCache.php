<?php

namespace Iyuu\SiteManager\Cache;

use Throwable;

/**
 * 用户信息缓存
 */
class UserProfileCache extends BaseCache
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->setKey('user_profile');
        $this->init();
    }

    /**
     * 工厂方法
     * @return self
     */
    public static function factory(): self
    {
        return new static();
    }

    /**
     * @return void
     */
    protected function init(): void
    {
        try {
            if (iyuu_token() && is_null($this->isVip())) {
                $profile = iyuu_reseed_client()->profile()['data'];
                $is_ever_level = (bool)$profile['is_ever_level'];
                $is_vip = $is_ever_level || time() < $profile['overdue_time'];
                if ($is_vip) {
                    $this->set(true, (int)min(86400 * 30, $profile['overdue_time'] - time()));
                } else {
                    $this->set(false, 300);
                }
            }
        } catch (Throwable $throwable) {
        }
    }

    /**
     * 是否是会员
     * @return bool|null
     */
    public function isVip(): ?bool
    {
        return $this->get();
    }
}
