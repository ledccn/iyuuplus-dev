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

namespace Guanguans\Notify\WPush\Messages;

/**
 * @method self channel($channel)
 * @method self content($content)
 * @method self title($title)
 * @method self topicCode($topicCode)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $required = [
        // 'apikey',
        // 'title',
    ];
    protected array $defined = [
        'title',
        'content',
        'channel',
        'topic_code',
    ];
    protected array $allowedValues = [
        // 'channel' => ['wechat', 'webhook', 'feishu', 'feishu', 'dingtalk', 'wechat_work', 'mail', 'sms'],
    ];

    public function toHttpUri(): string
    {
        return 'api/v1/send';
    }
}
