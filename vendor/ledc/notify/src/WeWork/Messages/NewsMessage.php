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
 * @method self articles(array $articles)
 */
class NewsMessage extends Message
{
    protected array $defined = [
        'articles',
    ];
    protected array $allowedTypes = [
        'articles' => 'array',
    ];
    protected array $options = [
        'articles' => [],
    ];

    public function addArticle(array $article): self
    {
        $this->options['articles'][] = $article;

        return $this;
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('articles', static function (OptionsResolver $optionsResolver): void {
            $optionsResolver
                ->setPrototype(true)
                ->setDefined([
                    'title',
                    'description',
                    'url',
                    'picurl',
                ]);
        });
    }

    protected function type(): string
    {
        return 'news';
    }
}
