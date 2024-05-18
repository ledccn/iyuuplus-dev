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

namespace Guanguans\Notify\Mattermost;

/**
 * @see https://api.mattermost.com
 *
 * ```
 * // user login.
 * curl --location --request POST 'https://guanguans.cloud.mattermost.com/api/v4/users/login' \
 * --header 'Content-Type: application/json' \
 * --data-raw '{"login_id":"{{user_name}}","password":"{{password}}"}'
 *
 * // channel list.
 * curl --location --request GET 'https://guanguans.cloud.mattermost.com/api/v4/channels' \
 * --header 'Authorization: Bearer {{token}}' \
 * --header 'Content-Type: application/json' \
 *
 * // send a post.
 * curl --location --request POST 'https://guanguans.cloud.mattermost.com/api/v4/posts' \
 * --header 'Authorization: Bearer {{token}}' \
 * --header 'Content-Type: application/json' \
 * --data-raw '{"message":"This is a testing.","channel_id":"{{channel_id}}"}'
 * ```
 */
class Client extends \Guanguans\Notify\Foundation\Client
{
    public function __construct(Authenticator $authenticator)
    {
        parent::__construct($authenticator);
    }
}
