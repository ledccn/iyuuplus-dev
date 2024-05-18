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

use Guanguans\Notify\Foundation\Support\Arr;

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
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $defined = [
        'groupId',

        'title',
        'body',
        'sound',
        'channel',
        'link',
        'image',
        'timeSensitive',
    ];
    protected array $allowedTypes = [
        'timeSensitive' => 'bool',
    ];

    public function toHttpUri(): string
    {
        return 'api/v1/notify/{token}';
    }

    protected function toPayload(): array
    {
        return Arr::except(parent::toPayload(), ['groupId']);
    }
}
