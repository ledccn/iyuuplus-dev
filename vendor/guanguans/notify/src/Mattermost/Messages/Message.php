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

namespace Guanguans\Notify\Mattermost\Messages;

/**
 * @method self channelId($channelId)
 * @method self createAt($createAt)
 * @method self editAt($editAt)
 * @method self fileIds(array $fileIds)
 * @method self isPinned(bool $isPinned)
 * @method self message($message)
 * @method self originalId($originalId)
 * @method self participants($participants)
 * @method self pendingPostId($pendingPostId)
 * @method self props(array $props)
 * @method self rootId($rootId)
 * @method self type($type)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $required = [
        // 'channel_id',
    ];
    protected array $defined = [
        'channel_id',
        'message',
        'file_ids',
        'create_at',
        'edit_at',
        'is_pinned',
        'root_id',
        'original_id',
        'type',
        'props',
        'pending_post_id',
        'participants',
    ];
    protected array $allowedTypes = [
        'file_ids' => 'array',
        'props' => 'array',
        'is_pinned' => 'bool',
    ];

    public function toHttpUri(): string
    {
        return 'api/v4/posts';
    }
}
