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

namespace Guanguans\Notify\WeWork\Messages;

/**
 * @method self content($content)
 * @method self mentionedList(array $mentionedList)
 * @method self mentionedMobileList(array $mentionedMobileList)
 */
class TextMessage extends Message
{
    protected array $defined = [
        'content',
        'mentioned_list',
        'mentioned_mobile_list',
    ];
    protected array $allowedTypes = [
        'mentioned_list' => 'array',
        'mentioned_mobile_list' => 'array',
    ];

    protected function type(): string
    {
        return 'text';
    }
}
