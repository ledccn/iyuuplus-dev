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
 * @method self address($address)
 * @method self chatId($chatId)
 * @method self disableNotification($disableNotification)
 * @method self foursquareId($foursquareId)
 * @method self foursquareType($foursquareType)
 * @method self googlePlaceId($googlePlaceId)
 * @method self googlePlaceType($googlePlaceType)
 * @method self latitude($latitude)
 * @method self longitude($longitude)
 * @method self messageThreadId($messageThreadId)
 * @method self protectContent($protectContent)
 * @method self replyMarkup($replyMarkup)
 * @method self replyParameters($replyParameters)
 * @method self title($title)
 */
class VenueMessage extends Message
{
    protected array $defined = [
        'chat_id',
        'message_thread_id',
        'latitude',
        'longitude',
        'title',
        'address',
        'foursquare_id',
        'foursquare_type',
        'google_place_id',
        'google_place_type',
        'disable_notification',
        'protect_content',
        'reply_parameters',
        'reply_markup',
    ];

    public function toHttpUri(): string
    {
        return 'bot{token}/sendVenue';
    }
}
