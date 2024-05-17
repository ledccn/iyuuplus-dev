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

namespace Guanguans\Notify\RocketChat;

/**
 * @see https://docs.rocket.chat/guides/administration/admin-panel/integrations
 *
 * ```
 * curl --location --request POST 'https://guanguans.rocket.chat/hooks/{{token}}' \
 * --header 'Content-Type: application/json' \
 * --data-raw '{"alias":"chatbot","emoji":":ghost:","text":"This is a testing.","attachments":[]}'
 * ```
 * @see https://developer.rocket.chat/reference/api
 *
 * ```
 * // login.
 * curl --location --request POST 'https://guanguans.rocket.chat/api/v1/login' \
 * --header 'Content-type: application/json' \
 * --data-raw '{"user": "{{user_name}}", "password": "{{password}}'.
 *
 * // channels list.
 * curl --location --request GET 'https://guanguans.rocket.chat/api/v1/channels.list' \
 * --header 'X-User-Id: {{user_id}}' \
 * --header 'X-Auth-Token: {{auth_token}}' \
 * --header 'Content-type: application/json' \
 *
 * // send message.
 * curl --location --request POST 'https://guanguans.rocket.chat/api/v1/chat.postMessage' \
 * --header 'X-User-Id: {{user_id}}' \
 * --header 'X-Auth-Token: {{auth_token}}' \
 * --header 'Content-type: application/json' \
 * --data-raw '{"roomId": "{{room_id}}", "text": "This is a testing."}'
 * ```
 */
class Client extends \Guanguans\Notify\Foundation\Client
{
    public function __construct(Authenticator $authenticator)
    {
        parent::__construct($authenticator);
    }
}
