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

namespace Guanguans\Notify\Pushback\Messages;

/**
 * @method self action1($action1)
 * @method self action2($action2)
 * @method self body($body)
 * @method self id($id)
 * @method self reply($reply)
 * @method self title($title)
 */
class SyncMessage extends Message
{
    public function toHttpUri(): string
    {
        return 'v1/send_sync';
    }
}
