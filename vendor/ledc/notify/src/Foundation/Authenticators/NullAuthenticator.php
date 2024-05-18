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

use Guanguans\Notify\Foundation\Contracts;
use Psr\Http\Message\RequestInterface;

class NullAuthenticator implements Contracts\Authenticator
{
    public function applyToOptions(array $options): array
    {
        return $options;
    }

    public function applyToRequest(RequestInterface $request): RequestInterface
    {
        return $request;
    }
}
