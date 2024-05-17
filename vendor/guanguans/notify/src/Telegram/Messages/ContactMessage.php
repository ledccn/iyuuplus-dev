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
 * @method self firstName($firstName)
 * @method self lastName($lastName)
 * @method self messageThreadId($messageThreadId)
 * @method self phoneNumber($phoneNumber)
 * @method self protectContent($protectContent)
 * @method self replyMarkup($replyMarkup)
 * @method self replyParameters($replyParameters)
 * @method self vcard($vcard)
 */
class ContactMessage extends Message
{
    protected array $defined = [
        'chat_id',
        'message_thread_id',
        'phone_number',
        'first_name',
        'last_name',
        'vcard',
        'disable_notification',
        'protect_content',
        'reply_parameters',
        'reply_markup',
    ];

    public function toHttpUri(): string
    {
        return 'bot{token}/sendContact';
    }
}
