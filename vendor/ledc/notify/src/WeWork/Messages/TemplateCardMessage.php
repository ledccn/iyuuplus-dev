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

namespace Guanguans\Notify\WeWork\Messages;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method self cardAction(array $cardAction)
 * @method self cardImage(array $cardImage)
 * @method self cardType($cardType)
 * @method self emphasisContent(array $emphasisContent)
 * @method self horizontalContentList(array $horizontalContentList)
 * @method self imageTextArea(array $imageTextArea)
 * @method self jumpList(array $jumpList)
 * @method self mainTitle(array $mainTitle)
 * @method self quoteArea(array $quoteArea)
 * @method self source(array $source)
 * @method self subTitleText($subTitleText)
 * @method self verticalContentList(array $verticalContentList)
 */
class TemplateCardMessage extends Message
{
    protected array $defined = [
        'card_type',
        'source',
        'main_title',
        'emphasis_content',
        'quote_area',
        'sub_title_text',
        'horizontal_content_list',
        'jump_list',
        'card_action',

        'card_image',
        'image_text_area',
        'vertical_content_list',
    ];
    protected array $allowedTypes = [
        'source' => 'array',
        'main_title' => 'array',
        'emphasis_content' => 'array',
        'quote_area' => 'array',
        'horizontal_content_list' => 'array',
        'jump_list' => 'array',
        'card_action' => 'array',
        'card_image' => 'array',
        'image_text_area' => 'array',
        'vertical_content_list' => 'array',
    ];
    protected array $options = [
        'horizontal_content_list' => [],
        'jump_list' => [],
        'vertical_content_list' => [],
    ];

    public function addHorizontalContent(array $horizontalContent): self
    {
        $this->options['horizontal_content_list'][] = $horizontalContent;

        return $this;
    }

    public function addJump(array $jump): self
    {
        $this->options['jump_list'][] = $jump;

        return $this;
    }

    public function addVerticalContent(array $verticalContent): self
    {
        $this->options['vertical_content_list'][] = $verticalContent;

        return $this;
    }

    protected function type(): string
    {
        return 'template_card';
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver
            ->setDefault('source', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver->setDefined([
                    'icon_url',
                    'desc',
                    'desc_color',
                ]);
            })
            ->setDefault('main_title', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver->setDefined([
                    'title',
                    'desc',
                ]);
            })
            ->setDefault('emphasis_content', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver->setDefined([
                    'title',
                    'desc',
                ]);
            })
            ->setDefault('quote_area', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver->setDefined([
                    'type',
                    'url',
                    'appid',
                    'pagepath',
                    'title',
                    'quote_text',
                ]);
            })
            ->setDefault('card_action', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver->setDefined([
                    'type',
                    'url',
                    'appid',
                    'pagepath',
                ]);
            })
            ->setDefault('card_image', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver->setDefined([
                    'url',
                    'aspect_ratio',
                ]);
            })
            ->setDefault('image_text_area', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver->setDefined([
                    'type',
                    'url',
                    'appid',
                    'pagepath',
                    'title',
                    'desc',
                    'image_url',
                ]);
            })
            ->setDefault('horizontal_content_list', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver
                    ->setPrototype(true)
                    ->setDefined([
                        'type',
                        'keyname',
                        'value',
                        'url',
                        'media_id',
                        'userid',
                    ]);
            })
            ->setDefault('jump_list', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver
                    ->setPrototype(true)
                    ->setDefined([
                        'type',
                        'title',
                        'url',
                        'appid',
                        'pagepath',
                    ]);
            })
            ->setDefault('vertical_content_list', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver
                    ->setPrototype(true)
                    ->setDefined([
                        'title',
                        'desc',
                    ]);
            });
    }
}
