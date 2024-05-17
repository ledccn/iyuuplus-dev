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

namespace Guanguans\Notify\NowPush\Messages;

/**
 * @method self deviceType($deviceType)
 * @method self messageType($messageType)
 * @method self note($note)
 * @method self url($url)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $defined = [
        'message_type',
        'note',
        'device_type',
        'url',
    ];
    protected array $allowedValues = [
        // 'message_type' => ['nowpush_note', 'nowpush_img', 'nowpush_link'],
        'device_type' => 'api',
    ];
    protected array $options = [
        'device_type' => 'api',
    ];

    public function toHttpUri(): string
    {
        return 'v3/sendMessage';
    }
}
