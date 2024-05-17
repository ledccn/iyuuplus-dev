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

namespace Guanguans\Notify\Lark\Messages;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method self post(array $post)
 */
class PostMessage extends Message
{
    protected array $defined = [
        'post',
    ];
    protected array $allowedTypes = [
        'post' => 'array',
    ];
    protected array $options = [
        'post' => [],
    ];

    public function setPostForLang(string $lang, array $post): self
    {
        $this->options['post'][$lang] = $post;

        return $this;
    }

    protected function type(): string
    {
        return 'post';
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('post', static function (OptionsResolver $optionsResolver): void {
            $optionsResolver
                ->setPrototype(true)
                ->setDefined([
                    'title',
                    'content',
                ])
                ->setAllowedTypes('content', 'array');
        });
    }
}
