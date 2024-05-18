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

namespace Guanguans\Notify\Lark;

use Guanguans\Notify\Foundation\Authenticators\AggregateAuthenticator;
use Guanguans\Notify\Foundation\Authenticators\OptionsAuthenticator;
use Guanguans\Notify\Foundation\Authenticators\TokenUriTemplateAuthenticator;
use GuzzleHttp\RequestOptions;

class Authenticator extends AggregateAuthenticator
{
    public function __construct(string $token, ?string $secret = null)
    {
        $authenticators = [new TokenUriTemplateAuthenticator($token)];

        if ($secret) {
            $authenticators[] = new OptionsAuthenticator([
                RequestOptions::JSON => [
                    'timestamp' => $timestamp = time(),
                    'sign' => $this->sign($secret, $timestamp),
                ],
            ]);
        }

        parent::__construct(...$authenticators);
    }

    private function sign(string $secret, int $timestamp): string
    {
        return base64_encode(hash_hmac('sha256', '', sprintf("%s\n%s", $timestamp, $secret), true));
    }
}
