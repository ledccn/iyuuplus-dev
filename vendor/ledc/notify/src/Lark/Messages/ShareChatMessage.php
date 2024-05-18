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

/**
 * @method self shareChatId($shareChatId)
 */
class ShareChatMessage extends Message
{
    protected array $defined = [
        'share_chat_id',
    ];

    protected function type(): string
    {
        return 'share_chat';
    }
}
