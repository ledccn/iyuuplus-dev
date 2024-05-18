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

use Guanguans\Notify\Foundation\Support\Arr;

abstract class Message extends \Guanguans\Notify\Foundation\Message
{
    final public function toHttpUri(): string
    {
        return 'robot/send';
    }

    protected function toPayload(): array
    {
        $payload = parent::toPayload();

        return [
            'msgtype' => $this->type(),
            $this->type() => Arr::except($payload, $atKeys = ['atMobiles', 'atDingtalkIds', 'isAtAll']),
            'at' => Arr::only($payload, $atKeys),
        ];
    }

    abstract protected function type(): string;
}
