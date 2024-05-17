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

namespace Guanguans\Notify\Bark\Messages;

/**
 * @method self autoCopy($autoCopy)
 * @method self badge($badge)
 * @method self body($body)
 * @method self copy($copy)
 * @method self group($group)
 * @method self icon($icon)
 * @method self isArchive($isArchive)
 * @method self level($level)
 * @method self sound($sound)
 * @method self title($title)
 * @method self url($url)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $defined = [
        'title',
        'body',
        'level',
        'badge',
        'autoCopy',
        'copy',
        'sound',
        'icon',
        'group',
        'isArchive',
        'url',
    ];
    protected array $allowedValues = [
        // 'level' => ['active', 'timeSensitive', 'passive'],
    ];

    public function toHttpUri(): string
    {
        return '{token}';
    }
}
