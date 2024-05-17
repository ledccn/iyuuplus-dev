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

namespace Guanguans\Notify\Gitter;

/**
 * List rooms.
 * ```
 * curl --location --request GET 'https://api.gitter.im/v1/rooms' \
 * --header 'Accept: application/json' \
 * --header 'Authorization: Bearer {{token}}'.
 * ```.
 *
 * Send a Message.
 * ```
 * curl --location --request POST 'https://api.gitter.im/v1/rooms/{{room_id}}/chatMessages' \
 * --header 'Content-Type: application/json' \
 * --header 'Accept: application/json' \
 * --header 'Authorization: Bearer {{token}}' \
 * --data-raw '{"text":"This is a testing."}'
 * ```
 */
class Client extends \Guanguans\Notify\Foundation\Client
{
    public function __construct(Authenticator $authenticator)
    {
        parent::__construct($authenticator);
        $this->baseUri('https://api.gitter.im/');
    }
}
