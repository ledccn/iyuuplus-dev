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

namespace Guanguans\Notify\Pushback;

/**
 * @see https://pushback.io/docs/getting-started
 *
 * ```
 * curl https://api.pushback.io/v1/send_sync \
 * -u at_uDCCK8gdHJPN613lASV: \
 * -d 'id=User_1730' \
 * -d 'title=Send notifications' \
 * -d 'body=Get responses back' \
 * -d 'action1=Action1' \
 * -d 'action2=Action2' \
 * -d 'reply=Reply'
 * ```
 */
class Client extends \Guanguans\Notify\Foundation\Client
{
    public function __construct(Authenticator $authenticator)
    {
        parent::__construct($authenticator);
        $this->baseUri('https://api.pushback.io/');
    }
}
