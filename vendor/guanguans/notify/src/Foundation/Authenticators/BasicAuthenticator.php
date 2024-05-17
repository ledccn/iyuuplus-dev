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

namespace Guanguans\Notify\Foundation\Authenticators;

use GuzzleHttp\RequestOptions;

/**
 * @see https://github.com/saloonphp/saloon/blob/v3/src/Http/Auth/BasicAuthenticator.php
 * @see https://github.com/phanxipang/fansipan/blob/main/src/Middleware/Auth/BasicAuthentication.php
 * @see https://github.com/kriswallsmith/Buzz/blob/master/lib/Middleware/BasicAuthMiddleware.php
 * @see https://github.com/guzzle/guzzle/blob/7.8/src/Client.php#L400
 */
class BasicAuthenticator extends OptionsAuthenticator
{
    public function __construct(string $username, string $password, string $type = 'basic')
    {
        parent::__construct([RequestOptions::AUTH => [$username, $password, $type]]);
    }
}
