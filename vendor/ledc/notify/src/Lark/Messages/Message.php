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

namespace Guanguans\Notify\Lark\Messages;

abstract class Message extends \Guanguans\Notify\Foundation\Message
{
    final public function toHttpUri(): string
    {
        return 'open-apis/bot/v2/hook/{token}';
    }

    protected function toPayload(): array
    {
        return [
            'msg_type' => $this->type(),
            'content' => parent::toPayload(),
        ];
    }

    abstract protected function type(): string;
}
