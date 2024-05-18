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

use Guanguans\Notify\Foundation\Concerns\AsMultipart;

class Message extends \Guanguans\Notify\Foundation\Message
{
    use AsMultipart;

    public function toHttpUri(): string
    {
        return 'v1/sender/{token}';
    }
}
