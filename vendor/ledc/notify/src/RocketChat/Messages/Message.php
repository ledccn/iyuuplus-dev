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

namespace Guanguans\Notify\RocketChat\Messages;

use Guanguans\Notify\Foundation\Concerns\AsNullUri;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method self alias($alias)
 * @method self attachments(array $attachments)
 * @method self avatar($avatar)
 * @method self channel($channel)
 * @method self emoji($emoji)
 * @method self roomId($roomId)
 * @method self text($text)
 * @method self tmid($tmid)
 * @method self tshow(bool $tshow)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    use AsNullUri;
    protected array $defined = [
        'roomId',
        'channel',
        'text',
        'alias',
        'emoji',
        'avatar',
        'attachments',
        'tmid',
        'tshow',
    ];
    protected $allowedTypes = [
        'attachments' => 'array',
        'tshow' => 'bool',
    ];
    protected array $options = [
        'attachments' => [],
    ];

    public function addAttachment(array $attachment): self
    {
        $this->options['attachments'][] = $attachment;

        return $this;
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('attachments', static function (OptionsResolver $optionsResolver): void {
            $optionsResolver
                ->setPrototype(true)
                ->setDefined([
                    'color',
                    'text',
                    'ts',
                    'thumb_url',
                    'message_link',
                    'collapsed',
                    'author_name',
                    'author_link',
                    'author_icon',
                    'title',
                    'title_link',
                    'title_link_download',
                    'image_url',
                    'audio_url',
                    'video_url',
                    'fields',
                ])
                ->setAllowedTypes('collapsed', 'bool')
                ->setAllowedTypes('title_link_download', 'bool')
                ->setAllowedTypes('fields', 'array')
                ->setDefault('fields', static function (OptionsResolver $optionsResolver): void {
                    $optionsResolver
                        ->setPrototype(true)
                        ->setDefined([
                            'short',
                            'title',
                            'value',
                        ])
                        ->setAllowedTypes('short', 'bool');
                });
        });
    }
}
