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
 * @method self allowsMultipleAnswers($allowsMultipleAnswers)
 * @method self chatId($chatId)
 * @method self closeDate($closeDate)
 * @method self correctOptionId($correctOptionId)
 * @method self disableNotification($disableNotification)
 * @method self explanation($explanation)
 * @method self explanationEntities($explanationEntities)
 * @method self explanationParseMode($explanationParseMode)
 * @method self isAnonymous($isAnonymous)
 * @method self isClosed($isClosed)
 * @method self messageThreadId($messageThreadId)
 * @method self openPeriod($openPeriod)
 * @method self options($options)
 * @method self protectContent($protectContent)
 * @method self question($question)
 * @method self replyMarkup($replyMarkup)
 * @method self replyParameters($replyParameters)
 * @method self type($type)
 */
class PollMessage extends Message
{
    protected array $defined = [
        'chat_id',
        'message_thread_id',
        'question',
        'options',
        'is_anonymous',
        'type',
        'allows_multiple_answers',
        'correct_option_id',
        'explanation',
        'explanation_parse_mode',
        'explanation_entities',
        'open_period',
        'close_date',
        'is_closed',
        'disable_notification',
        'protect_content',
        'reply_parameters',
        'reply_markup',
    ];
    protected array $options = [
        'explanation_entities' => [],
    ];

    public function addExplanationEntity(array $explanationEntity): self
    {
        $this->options['explanation_entities'][] = $explanationEntity;

        return $this;
    }

    public function toHttpUri(): string
    {
        return 'bot{token}/sendPoll';
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver
            ->setAllowedTypes('options', 'array')
            ->setNormalizer(
                'options',
                static fn (Options $options, array $value): string => json_encode($value, \JSON_THROW_ON_ERROR),
            );
    }
}
