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

namespace Guanguans\Notify\Chanify\Messages;

/**
 * @method self link($link)
 * @method self priority($priority)
 * @method self sound($sound)
 */
class LinkMessage extends Message
{
    protected array $defined = [
        'link',
        'sound',
        'priority',
    ];
}
