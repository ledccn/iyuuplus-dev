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

namespace Guanguans\Notify\XiZhi\Messages;

/**
 * @method self content($content)
 * @method self date($date)
 * @method self time($time)
 * @method self title($title)
 * @method self type($type)
 */
class SingleMessage extends Message
{
    public function toHttpUri(): string
    {
        return '{token}.send';
    }
}
