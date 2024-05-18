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

namespace Guanguans\Notify\MicrosoftTeams\Messages;

use Guanguans\Notify\Foundation\Concerns\AsNullUri;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method self correlationId($correlationId)
 * @method self expectedActors(array $expectedActors)
 * @method self hideOriginalBody(bool $hideOriginalBody)
 * @method self originator($originator)
 * @method self potentialAction(array $potentialAction)
 * @method self sections(array $sections)
 * @method self summary($summary)
 * @method self text($text)
 * @method self themeColor($themeColor)
 * @method self title($title)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    use AsNullUri;
    protected array $defined = [
        '@type',
        '@context',
        'correlationId',
        'expectedActors',
        'originator',
        'summary',
        'themeColor',
        'hideOriginalBody',
        'title',
        'text',
        'sections',
        'potentialAction',
    ];
    protected $allowedTypes = [
        'hideOriginalBody' => 'bool',
        'expectedActors' => 'array',
        'sections' => 'array',
        'potentialAction' => 'array',
    ];
    protected array $options = [
        '@type' => 'MessageCard',
        '@context' => 'https://schema.org/extensions',
        'expectedActors' => [],
        'sections' => [],
        'potentialAction' => [],
    ];

    public function addSection(array $section): self
    {
        $this->options['sections'][] = $section;

        return $this;
    }

    public function addPotentialAction(array $potentialAction): self
    {
        $this->options['potentialAction'][] = $potentialAction;

        return $this;
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver
            ->setDefault('sections', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver
                    ->setPrototype(true)
                    ->setDefined([
                        'title',
                        'startGroup',
                        'activityImage',
                        'activityTitle',
                        'activitySubtitle',
                        'activityText',
                        'heroImage',
                        'text',
                        'facts',
                        'images',
                        'potentialAction',
                    ])
                    ->setAllowedTypes('startGroup', 'bool')
                    ->setAllowedTypes('facts', 'array')
                    ->setAllowedTypes('images', 'array')
                    ->setAllowedTypes('potentialAction', 'array');
            })
            ->setDefault('potentialAction', static function (OptionsResolver $optionsResolver): void {
                $optionsResolver
                    ->setPrototype(true)
                    ->setDefined([
                        '@type',
                        'name',
                        'targets',

                        'target',
                        'headers',
                        'body',
                        'bodyContentType',

                        'inputs',
                        'actions',

                        'addInId',
                        'desktopCommandId',
                        'initializationContext',
                    ])
                    ->setAllowedTypes('targets', 'array')
                    ->setAllowedTypes('headers', 'array')
                    ->setAllowedTypes('inputs', 'array')
                    ->setAllowedTypes('actions', 'array')
                    ->setAllowedTypes('initializationContext', 'array')
                    ->addAllowedValues('@type', [
                        'OpenUri',
                        'ActionCard',
                        'HttpPOST',
                        'InvokeAddInCommand',
                    ])
                    ->addAllowedValues('bodyContentType', [
                        'application/x-www-form-urlencoded',
                        'application/json',
                    ]);
            });
    }
}
