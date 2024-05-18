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
 * @method self heading($heading)
 * @method self horizontalAccuracy($horizontalAccuracy)
 * @method self latitude($latitude)
 * @method self livePeriod($livePeriod)
 * @method self longitude($longitude)
 * @method self messageThreadId($messageThreadId)
 * @method self protectContent($protectContent)
 * @method self proximityAlertRadius($proximityAlertRadius)
 * @method self replyMarkup($replyMarkup)
 * @method self replyParameters($replyParameters)
 */
class LocationMessage extends Message
{
    protected array $defined = [
        'chat_id',
        'message_thread_id',
        'latitude',
        'longitude',
        'horizontal_accuracy',
        'live_period',
        'heading',
        'proximity_alert_radius',
        'disable_notification',
        'protect_content',
        'reply_parameters',
        'reply_markup',
    ];

    public function toHttpUri(): string
    {
        return 'bot{token}/sendLocation';
    }
}
