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

namespace Guanguans\Notify\DingTalk\Messages;

/**
 * @method self btnOrientation($btnOrientation)
 * @method self singleTitle($singleTitle)
 * @method self singleURL($singleURL)
 * @method self text($text)
 * @method self title($title)
 */
class SingleActionCardMessage extends Message
{
    protected array $defined = [
        'title',
        'text',
        'singleTitle',
        'singleURL',
        'btnOrientation',
    ];

    protected function type(): string
    {
        return 'actionCard';
    }
}
