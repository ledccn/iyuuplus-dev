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

namespace Guanguans\Notify\AnPush\Messages;

use Guanguans\Notify\Foundation\Concerns\AsFormParams;

/**
 * @method self channel($channel)
 * @method self content($content)
 * @method self title($title)
 * @method self to($to)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    use AsFormParams;
    protected array $required = [
        // 'title',
        // 'channel',
    ];
    protected array $defined = [
        'title',
        'content',
        'channel',
        'to',
    ];

    public function toHttpUri(): string
    {
        return 'push/{token}';
    }
}
