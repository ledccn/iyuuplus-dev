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

namespace Guanguans\Notify\PushPlus\Messages;

/**
 * @method self callbackUrl($callbackUrl)
 * @method self channel($channel)
 * @method self content($content)
 * @method self template($template)
 * @method self timestamp($timestamp)
 * @method self title($title)
 * @method self to($to)
 * @method self topic($topic)
 * @method self webhook($webhook)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $required = [
        // 'content',
    ];
    protected array $defined = [
        'topic', // 群组消息
        'to', // 好友消息

        'title',
        'content',
        'template',
        'channel',
        'webhook',
        'callbackUrl',
        'timestamp',
    ];

    public function toHttpUri(): string
    {
        return 'send/{token}';
    }
}
