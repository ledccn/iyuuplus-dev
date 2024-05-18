<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021-2024 guanguans<ityaozm@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/guanguans/notify
 */

namespace Guanguans\Notify\Push\Messages;

/**
 * @method self body($body)
 * @method self channel($channel)
 * @method self groupId($groupId)
 * @method self image($image)
 * @method self link($link)
 * @method self sound($sound)
 * @method self timeSensitive(bool $timeSensitive)
 * @method self title($title)
 */
class AsyncMessage extends Message
{
    public function toHttpUri(): string
    {
        return 'api/v1/notify-async';
    }
}
