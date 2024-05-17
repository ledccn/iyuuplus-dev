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

namespace Guanguans\Notify\ShowdocPush\Messages;

/**
 * @method self content($content)
 * @method self title($title)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $defined = [
        'title',
        'content',
    ];

    public function toHttpUri(): string
    {
        return 'server/api/push/{token}';
    }
}
