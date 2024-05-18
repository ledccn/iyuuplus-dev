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

namespace Guanguans\Notify\Discord\Messages;

use Guanguans\Notify\Foundation\Concerns\AsNullUri;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method self allowedMentions(array $allowedMentions)
 * @method self attachments(array $attachments)
 * @method self avatarUrl($avatarUrl)
 * @method self components(array $components)
 * @method self content($content)
 * @method self embeds(array $embeds)
 * @method self files(array $files)
 * @method self payloadJson($payloadJson)
 * @method self tts(bool $tts)
 * @method self username($username)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    use AsNullUri;
    protected array $defined = [
        'content',
        'embeds',
        'allowed_mentions',
        'components',
        'files',
        'payload_json',
        'attachments',

        'username',
        'avatar_url',
        'tts',
    ];
    protected array $allowedTypes = [
        'tts' => 'bool',
        'embeds' => 'array',
        'allowed_mentions' => 'array',
        'components' => 'array',
        'files' => 'array',
        'attachments' => 'array',
    ];
    protected array $options = [
        'embeds' => [],
    ];

    public function addEmbed(array $embed): self
    {
        $this->options['embeds'][] = $embed;

        return $this;
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('embeds', static function (OptionsResolver $optionsResolver): void {
            $optionsResolver
                ->setPrototype(true)
                ->setDefined([
                    'title',
                    'type',
                    'description',
                    'url',
                    'timestamp',
                    'color',
                    'footer',
                    'image',
                    'thumbnail',
                    'video',
                    'provider',
                    'author',
                    'fields',
                ])
                ->setNormalizer('color', static fn (
                    OptionsResolver $optionsResolver,
                    $value
                ) => \is_int($value) ? $value : hexdec($value))
                ->setAllowedTypes('footer', 'array')
                ->setDefault('footer', static function (OptionsResolver $optionsResolver): void {
                    $optionsResolver->setDefined([
                        'text',
                        'icon_url',
                        'proxy_icon_url',
                    ]);
                })
                ->setAllowedTypes('image', 'array')
                ->setDefault('image', static function (OptionsResolver $optionsResolver): void {
                    $optionsResolver->setDefined([
                        'url',
                        'proxy_url',
                        'height',
                        'width',
                    ]);
                })
                ->setAllowedTypes('thumbnail', 'array')
                ->setDefault('thumbnail', static function (OptionsResolver $optionsResolver): void {
                    $optionsResolver->setDefined([
                        'url',
                        'proxy_url',
                        'height',
                        'width',
                    ]);
                })
                ->setAllowedTypes('video', 'array')
                ->setDefault('video', static function (OptionsResolver $optionsResolver): void {
                    $optionsResolver->setDefined([
                        'url',
                        'proxy_url',
                        'height',
                        'width',
                    ]);
                })
                ->setAllowedTypes('provider', 'array')
                ->setDefault('provider', static function (OptionsResolver $optionsResolver): void {
                    $optionsResolver->setDefined([
                        'name',
                        'url',
                    ]);
                })
                ->setAllowedTypes('author', 'array')
                ->setDefault('author', static function (OptionsResolver $optionsResolver): void {
                    $optionsResolver->setDefined([
                        'name',
                        'url',
                        'icon_url',
                        'proxy_icon_url',
                    ]);
                })
                ->setAllowedTypes('fields', 'array')
                ->setDefault('fields', static function (OptionsResolver $optionsResolver): void {
                    $optionsResolver
                        ->setPrototype(true)
                        ->setDefined([
                            'name',
                            'value',
                            'inline',
                        ])
                        ->setAllowedTypes('inline', 'bool');
                });
        });
    }
}
