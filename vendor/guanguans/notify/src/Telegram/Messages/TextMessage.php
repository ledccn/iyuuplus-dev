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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method self chatId($chatId)
 * @method self disableNotification(bool $disableNotification)
 * @method self entities(array $entities)
 * @method self linkPreviewOptions(array $linkPreviewOptions)
 * @method self messageThreadId($messageThreadId)
 * @method self parseMode($parseMode)
 * @method self protectContent(bool $protectContent)
 * @method self replyMarkup(array $replyMarkup)
 * @method self replyParameters(array $replyParameters)
 * @method self text($text)
 */
class TextMessage extends Message
{
    protected array $defined = [
        'chat_id',
        'message_thread_id',
        'text',
        'parse_mode',
        'entities',
        'link_preview_options',
        'disable_notification',
        'protect_content',
        'reply_parameters',
        'reply_markup',
    ];
    protected array $allowedValues = [
        // 'parse_mode' => ['HTML', 'Markdown', 'MarkdownV2'],
    ];
    protected array $allowedTypes = [
        'entities' => 'array',
        'link_preview_options' => 'array',
        'disable_notification' => 'bool',
        'protect_content' => 'bool',
        'reply_parameters' => 'array',
        'reply_markup' => 'array',
    ];
    protected array $options = [
        'entities' => [],
    ];

    public function toHttpUri(): string
    {
        return 'bot{token}/sendMessage';
    }

    public function addEntity(array $entity): self
    {
        $this->options['entities'][] = $entity;

        return $this;
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver
            ->setDefault('entities', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver
                    ->setPrototype(true)
                    ->setDefined([
                        'type',
                        'offset',
                        'length',
                        'url',
                        'user',
                        'language',
                        'custom_emoji_id',
                    ])
                    ->setAllowedTypes('user', 'array')
                    ->setDefault('user', static function (OptionsResolver $optionsResolver): void {
                        $optionsResolver
                            ->setDefined([
                                'id',
                                'is_bot',
                                'first_name',
                                'last_name',
                                'username',
                                'language_code',
                                'is_premium',
                                'added_to_attachment_menu',
                                'can_join_groups',
                                'can_read_all_group_messages',
                                'supports_inline_queries',
                            ])
                            ->setAllowedTypes('is_bot', 'bool')
                            ->setAllowedTypes('is_premium', 'bool')
                            ->setAllowedTypes('can_join_groups', 'bool')
                            ->setAllowedTypes('can_read_all_group_messages', 'bool')
                            ->setAllowedTypes('supports_inline_queries', 'bool');
                    });
            })
            ->setDefault('link_preview_options', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver
                    ->setDefined([
                        'is_disabled',
                        'url',
                        'prefer_small_media',
                        'prefer_large_media',
                        'show_above_text',
                    ])
                    ->setAllowedTypes('is_disabled', 'bool')
                    ->setAllowedTypes('prefer_small_media', 'bool')
                    ->setAllowedTypes('prefer_large_media', 'bool')
                    ->setAllowedTypes('show_above_text', 'bool');
            })
            ->setDefault('reply_parameters', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver
                    ->setDefined([
                        'message_id',
                        'chat_id',
                        'allow_sending_without_reply',
                        'quote',
                        'quote_parse_mode',
                        'quote_entities',
                        'quote_position',
                    ])
                    ->setAllowedTypes('allow_sending_without_reply', 'bool')
                    ->setAllowedTypes('quote_entities', 'array')
                    ->setDefault('quote_entities', static function (OptionsResolver $optionsResolver): void {
                        $optionsResolver
                            ->setPrototype(true)
                            ->setDefined([
                                'type',
                                'offset',
                                'length',
                                'url',
                                'user',
                                'language',
                                'custom_emoji_id',
                            ])
                            ->setAllowedTypes('user', 'array')
                            ->setDefault('user', static function (OptionsResolver $optionsResolver): void {
                                $optionsResolver
                                    ->setDefined([
                                        'id',
                                        'is_bot',
                                        'first_name',
                                        'last_name',
                                        'username',
                                        'language_code',
                                        'is_premium',
                                        'added_to_attachment_menu',
                                        'can_join_groups',
                                        'can_read_all_group_messages',
                                        'supports_inline_queries',
                                    ])
                                    ->setAllowedTypes('is_bot', 'bool')
                                    ->setAllowedTypes('is_premium', 'bool')
                                    ->setAllowedTypes('can_join_groups', 'bool')
                                    ->setAllowedTypes('can_read_all_group_messages', 'bool')
                                    ->setAllowedTypes('supports_inline_queries', 'bool');
                            });
                    });
            })
            ->setDefault('reply_markup', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver
                    ->setDefined([
                        'inline_keyboard', // InlineKeyboardMarkup

                        'keyboard', // ReplyKeyboardMarkup
                        'is_persistent', // ReplyKeyboardMarkup
                        'resize_keyboard', // ReplyKeyboardMarkup
                        'one_time_keyboard', // ReplyKeyboardMarkup
                        'input_field_placeholder', // ReplyKeyboardMarkup
                        'selective', // ReplyKeyboardMarkup

                        'remove_keyboard', // ReplyKeyboardRemove
                        'selective', // ReplyKeyboardRemove

                        'force_reply', // ForceReply
                        'input_field_placeholder', // ForceReply
                        'selective', // ForceReply
                    ])
                    ->setAllowedTypes('inline_keyboard', 'array')
                    ->setAllowedTypes('keyboard', 'array')
                    ->setAllowedTypes('is_persistent', 'bool')
                    ->setAllowedTypes('resize_keyboard', 'bool')
                    ->setAllowedTypes('one_time_keyboard', 'bool')
                    ->setAllowedTypes('selective', 'bool')
                    ->setAllowedTypes('remove_keyboard', 'bool')
                    ->setAllowedTypes('force_reply', 'bool');
            });
    }
}
