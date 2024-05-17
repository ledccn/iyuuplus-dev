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

namespace Guanguans\Notify\Zulip;

use Guanguans\Notify\Foundation\Authenticators\BasicAuthenticator;

class Authenticator extends BasicAuthenticator
{
    public function __construct(string $botEmail, string $botApiKey)
    {
        parent::__construct($botEmail, $botApiKey);
    }
}
