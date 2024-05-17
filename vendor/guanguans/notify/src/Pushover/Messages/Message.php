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

namespace Guanguans\Notify\Pushover\Messages;

use Guanguans\Notify\Foundation\Concerns\AsMultipart;

/**
 * @method self attachment($attachment)
 * @method self callback($callback)
 * @method self device($device)
 * @method self expire($expire)
 * @method self html($html)
 * @method self message($message)
 * @method self monospace($monospace)
 * @method self priority($priority)
 * @method self retry($retry)
 * @method self sound($sound)
 * @method self timestamp($timestamp)
 * @method self title($title)
 * @method self ttl($ttl)
 * @method self url($url)
 * @method self urlTitle($urlTitle)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    use AsMultipart;
    protected array $required = [
        // // 'token',
        // // 'user',
        // 'message',
    ];
    protected array $defined = [
        // 'token',
        // 'user',
        'message',
        'attachment',
        // 'attachment_base64',
        // 'attachment_type',
        'device',
        'html',
        'priority',
        'sound',
        'timestamp',
        'title',
        'ttl',
        'url',
        'url_title',

        'retry',
        'expire',
        'monospace',
        'callback',
    ];

    public function toHttpUri(): string
    {
        return '1/messages.json';
    }
}
