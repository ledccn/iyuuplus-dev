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

namespace Guanguans\Notify\Iyuu\Message;

/**
 * 爱语飞飞.
 *
 * @method self desp($desp)
 * @method self text($title)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $defined = [
        'text',
        'desp',
    ];

    public function toHttpUri(): string
    {
        return '{token}.send';
    }
}
