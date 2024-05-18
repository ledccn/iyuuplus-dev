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

namespace Guanguans\Notify\Slack\Messages;

use Guanguans\Notify\Foundation\Concerns\AsNullUri;

/**
 * @method self asUser(bool $asUser)
 * @method self attachments(array $attachments)
 * @method self blocks(array $blocks)
 * @method self channel($channel)
 * @method self iconEmoji($iconEmoji)
 * @method self iconUrl($iconUrl)
 * @method self linkNames(bool $linkNames)
 * @method self metadata(array $metadata)
 * @method self mrkdwn(bool $mrkdwn)
 * @method self parse($parse)
 * @method self replyBroadcast(bool $replyBroadcast)
 * @method self text($text)
 * @method self threadTs($threadTs)
 * @method self unfurlLinks(bool $unfurlLinks)
 * @method self unfurlMedia(bool $unfurlMedia)
 * @method self username($username)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    use AsNullUri;
    protected array $defined = [
        'channel',
        'attachments',
        'blocks',
        'text',
        'as_user',
        'icon_emoji',
        'icon_url',
        'link_names',
        'metadata',
        'mrkdwn',
        'parse',
        'reply_broadcast',
        'thread_ts',
        'unfurl_links',
        'unfurl_media',
        'username',
    ];
    protected array $allowedTypes = [
        'attachments' => 'array',
        'blocks' => 'array',
        'as_user' => 'bool',
        'link_names' => 'bool',
        'metadata' => 'array',
        'mrkdwn' => 'bool',
        'reply_broadcast' => 'bool',
        'unfurl_links' => 'bool',
        'unfurl_media' => 'bool',
    ];
    protected array $options = [
        'attachments' => [],
        'blocks' => [],
    ];

    public function addAttachment(array $attachment): self
    {
        $this->options['attachments'][] = $attachment;

        return $this;
    }

    public function addBlock(array $block): self
    {
        $this->options['blocks'][] = $block;

        return $this;
    }
}
