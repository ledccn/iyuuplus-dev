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

namespace Guanguans\Notify\Telegram\Messages;

/**
 * @method self allowedUpdates(array $allowedUpdates)
 * @method self limit($limit)
 * @method self offset($offset)
 * @method self timeout($timeout)
 */
class GetUpdatesMessage extends Message
{
    protected array $defined = [
        'offset',
        'limit',
        'timeout',
        'allowed_updates',
    ];
    protected array $allowedTypes = [
        'allowed_updates' => 'array',
    ];

    public function toHttpUri(): string
    {
        return 'bot{token}/getUpdates';
    }
}
