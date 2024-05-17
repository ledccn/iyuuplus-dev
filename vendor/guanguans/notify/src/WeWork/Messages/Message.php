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

use Guanguans\Notify\Foundation\Concerns\AsBody;

abstract class Message extends \Guanguans\Notify\Foundation\Message
{
    // use AsBody;

    final public function toHttpUri(): string
    {
        return 'cgi-bin/webhook/send?key={token}';
    }

    protected function toPayload(): array
    {
        return [
            'msgtype' => $this->type(),
            $this->type() => parent::toPayload(),
        ];
    }

    abstract protected function type(): string;
}
