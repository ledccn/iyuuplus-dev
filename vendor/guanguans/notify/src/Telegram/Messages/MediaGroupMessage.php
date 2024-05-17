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

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method self chatId($chatId)
 * @method self disableNotification($disableNotification)
 * @method self media(array $media)
 * @method self messageThreadId($messageThreadId)
 * @method self protectContent($protectContent)
 * @method self replyParameters($replyParameters)
 */
class MediaGroupMessage extends Message
{
    protected array $defined = [
        'chat_id',
        'message_thread_id',
        'media',
        'disable_notification',
        'protect_content',
        'reply_parameters',
    ];
    protected array $allowedTypes = [
        'media' => 'array',
    ];
    protected array $options = [
        'media' => [],
    ];

    public function addMedia(array $media): self
    {
        $this->options['media'][] = $media;

        return $this;
    }

    public function toHttpUri(): string
    {
        return 'bot{token}/sendMediaGroup';
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setNormalizer(
            'media',
            static fn (Options $options, array $media): string => json_encode($media, \JSON_THROW_ON_ERROR),
        );
    }
}
