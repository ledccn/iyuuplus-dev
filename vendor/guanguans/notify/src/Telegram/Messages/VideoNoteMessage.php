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
 * @method self chatId($chatId)
 * @method self disableNotification($disableNotification)
 * @method self duration($duration)
 * @method self length($length)
 * @method self messageThreadId($messageThreadId)
 * @method self protectContent($protectContent)
 * @method self replyMarkup($replyMarkup)
 * @method self replyParameters($replyParameters)
 * @method self thumbnail($thumbnail)
 * @method self videoNote($videoNote)
 */
class VideoNoteMessage extends Message
{
    protected array $defined = [
        'chat_id',
        'message_thread_id',
        'video_note',
        'duration',
        'length',
        'thumbnail',
        'disable_notification',
        'protect_content',
        'reply_parameters',
        'reply_markup',
    ];

    public function toHttpUri(): string
    {
        return 'bot{token}/sendVideoNote';
    }
}
