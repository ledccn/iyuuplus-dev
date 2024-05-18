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

namespace Guanguans\Notify\Ntfy\Messages;

use Guanguans\Notify\Foundation\Concerns\AsNullUri;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method self actions(array $actions)
 * @method self attach($attach)
 * @method self cache($cache)
 * @method self call($call)
 * @method self click($click)
 * @method self delay($delay)
 * @method self email($email)
 * @method self filename($filename)
 * @method self firebase($firebase)
 * @method self icon($icon)
 * @method self markdown($markdown)
 * @method self message($message)
 * @method self pollId($pollId)
 * @method self priority($priority)
 * @method self tags(array $tags)
 * @method self title($title)
 * @method self topic($topic)
 * @method self unifiedPush($unifiedPush)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    use AsNullUri;
    protected array $required = [
        // 'topic',
    ];
    protected array $defined = [
        'topic',
        'message',
        'title',
        'tags',
        'priority',
        'actions',
        'click',
        'attach',
        'markdown',
        'icon',
        'filename',
        'delay',
        'email',
        'call',
        'cache',
        'firebase',
        'unified_push',
        'poll_id',
    ];
    protected array $allowedValues = [
        // 'priority' => [5, 4, 3, 2, 1],
        // 'cache' => ['yes', 'no'],
        // 'firebase' => ['yes', 'no'],
    ];
    protected array $allowedTypes = [
        'tags' => 'array',
        'actions' => 'array',
    ];
    protected array $options = [
        'actions' => [],
    ];

    public function addAction(array $action): self
    {
        $this->options['actions'][] = $action;

        return $this;
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('actions', static function (OptionsResolver $optionsResolver): void {
            $optionsResolver
                ->setPrototype(true)
                ->setDefined([
                    'action', // [view, broadcast, http, click]
                    'label',
                    'clear',

                    'url', // view|http|click
                    'intent', // broadcast
                    'extras', // broadcast
                    'method', // http
                    'headers', // http
                    'body', // http
                    'method', // http
                ])
                ->setAllowedTypes('clear', 'bool')
                ->setAllowedTypes('extras', 'array')
                ->setAllowedTypes('headers', 'array');
        });
    }
}
