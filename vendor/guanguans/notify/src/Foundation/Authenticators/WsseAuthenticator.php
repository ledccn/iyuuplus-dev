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

use Psr\Http\Message\RequestInterface;

class WsseAuthenticator extends NullAuthenticator
{
    private string $username;
    private string $password;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function applyToRequest(RequestInterface $request): RequestInterface
    {
        $nonce = substr(sha1(uniqid('', true)), 0, 16);
        $created = date('c');
        $digest = base64_encode(sha1(base64_decode($nonce, true).$created.$this->password, true));

        $wsse = sprintf(
            'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $this->username,
            $digest,
            $nonce,
            $created,
        );

        return $request
            ->withHeader('Authorization', 'WSSE profile="UsernameToken"')
            ->withHeader('X-WSSE', $wsse);
    }
}
