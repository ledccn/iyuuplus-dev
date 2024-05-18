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

namespace Guanguans\Notify\Foundation\Middleware;

use Guanguans\Notify\Foundation\Contracts\Authenticator;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

class Authenticate
{
    private Authenticator $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function __invoke(callable $handler): callable
    {
        return Middleware::mapRequest(
            fn (RequestInterface $request): RequestInterface => $this->authenticator->applyToRequest($request),
        )($handler);
    }
}
