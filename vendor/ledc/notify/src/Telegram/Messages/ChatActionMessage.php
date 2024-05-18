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

namespace Guanguans\Notify\Telegram\Messages;

/**
 * @method self action($action)
 * @method self chatId($chatId)
 * @method self messageThreadId($messageThreadId)
 */
class ChatActionMessage extends Message
{
    protected array $defined = [
        'chat_id',
        'message_thread_id',
        'action',
    ];

    public function toHttpUri(): string
    {
        return 'bot{token}/sendChatAction';
    }
}
