<?php

namespace app\locker;

use app\common\Locker;
use Symfony\Component\Lock\SharedLockInterface;

/**
 * 业务锁：PhinxLocker
 * @method static SharedLockInterface lock(?string $key = null, ?float $ttl = null, ?bool $autoRelease = null, ?string $prefix = null) 创建锁
 */
class PhinxLocker extends Locker
{
}
