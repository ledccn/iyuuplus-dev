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

namespace Guanguans\Notify\Slack;

/**
 * @see https://api.slack.com/messaging/webhooks
 *
 * ```
 * curl --location --request POST 'https://hooks.slack.com/services/TPU9A9/B038KNUC/6pKH3vfa3mjlUP' \
 * --header 'Content-Type: application/x-www-form-urlencoded' \
 * --data-urlencode 'payload={"text": "This is  text"}'
 * ```
 */
class Client extends \Guanguans\Notify\Foundation\Client
{
    public function __construct(Authenticator $authenticator)
    {
        parent::__construct($authenticator);
    }
}
