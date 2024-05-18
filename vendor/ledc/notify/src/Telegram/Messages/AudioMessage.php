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
 * @method self audio($audio)
 * @method self caption($caption)
 * @method self captionEntities($captionEntities)
 * @method self chatId($chatId)
 * @method self disableNotification($disableNotification)
 * @method self duration($duration)
 * @method self messageThreadId($messageThreadId)
 * @method self parseMode($parseMode)
 * @method self performer($performer)
 * @method self protectContent($protectContent)
 * @method self replyMarkup($replyMarkup)
 * @method self replyParameters($replyParameters)
 * @method self thumbnail($thumbnail)
 * @method self title($title)
 */
class AudioMessage extends Message
{
    protected array $defined = [
        'chat_id',
        'message_thread_id',
        'audio',
        'caption',
        'parse_mode',
        'caption_entities',
        'duration',
        'performer',
        'title',
        'thumbnail',
        'disable_notification',
        'protect_content',
        'reply_parameters',
        'reply_markup',
    ];
    protected array $options = [
        'caption_entities' => [],
    ];

    public function toHttpUri(): string
    {
        return 'bot{token}/sendAudio';
    }

    public function addCaptionEntity(array $captionEntity): self
    {
        $this->options['caption_entities'][] = $captionEntity;

        return $this;
    }
}
