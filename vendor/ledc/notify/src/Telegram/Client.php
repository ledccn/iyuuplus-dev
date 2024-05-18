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

namespace Guanguans\Notify\Telegram;

use Guanguans\Notify\Foundation\Contracts\Authenticator;

/**
 * @see https://telegram.me/botfather
 * @see https://core.telegram.org/bots/api#getupdates
 * @see https://api.telegram.org/botxxx:yyy/getUpdates
 * @see https://core.telegram.org/bots/api#sendmessage
 * @see https://api.telegram.org/botxxx:yyy/sendMessage?chat_id=xx&text=text
 */
class Client extends \Guanguans\Notify\Foundation\Client
{
    public function __construct(Authenticator $authenticator)
    {
        parent::__construct($authenticator);
        $this->baseUri('https://api.telegram.org/');
    }
}
