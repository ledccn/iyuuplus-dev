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

namespace Guanguans\Notify\Ntfy;

use Guanguans\Notify\Foundation\Authenticators\AggregateAuthenticator;
use Guanguans\Notify\Foundation\Authenticators\BasicAuthenticator;
use Guanguans\Notify\Foundation\Authenticators\BearerAuthenticator;
use Guanguans\Notify\Foundation\Authenticators\NullAuthenticator;
use Guanguans\Notify\Foundation\Authenticators\OptionsAuthenticator;
use Guanguans\Notify\Foundation\Exceptions\InvalidArgumentException;
use GuzzleHttp\RequestOptions;

class Authenticator extends AggregateAuthenticator
{
    public function __construct(?string $usernameOrToken = null, ?string $password = null)
    {
        switch (\func_num_args()) {
            case 0:
                $authenticators = [
                    new NullAuthenticator,
                ];

                break;
            case 1:
                $authenticators = [
                    new BearerAuthenticator($usernameOrToken),
                    new OptionsAuthenticator([
                        RequestOptions::QUERY => [RequestOptions::AUTH => $usernameOrToken],
                    ]),
                ];

                break;
            case 2:
                $authenticators = [
                    new BasicAuthenticator($usernameOrToken, $password),
                ];

                break;
            default:
                throw new InvalidArgumentException('The number of arguments must be 0, 1 or 2.');
        }

        parent::__construct(...$authenticators);
    }
}
