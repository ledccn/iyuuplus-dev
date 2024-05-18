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

namespace Guanguans\Notify\Chanify\Messages;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method self actions(array $actions)
 * @method self autocopy($autocopy)
 * @method self copy($copy)
 * @method self interruptionlevel($interruptionlevel)
 * @method self priority($priority)
 * @method self sound($sound)
 * @method self text($text)
 * @method self timeline(array $timeline)
 * @method self title($title)
 */
class TextMessage extends Message
{
    protected array $defined = [
        'title',
        'text',
        'copy',
        'autocopy',
        'sound',
        'priority',
        'interruptionlevel',
        'actions',
        'timeline',
    ];
    protected array $allowedValues = [
        // 'interruptionlevel' => ['active', 'passive', 'time-sensitive'],
    ];
    protected array $allowedTypes = [
        'actions' => 'array',
        'timeline' => 'array',
    ];
    protected array $options = [
        'actions' => [],
    ];

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('timeline', static function (OptionsResolver $optionsResolver): void {
            $optionsResolver
                ->setDefined([
                    'code',
                    'timestamp',
                    'items',
                ])
                ->setAllowedTypes('items', 'array');
        });
    }
}
