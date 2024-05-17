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

namespace Guanguans\Notify\DingTalk;

use Guanguans\Notify\Foundation\Authenticators\OptionsAuthenticator;
use GuzzleHttp\RequestOptions;

class Authenticator extends OptionsAuthenticator
{
    public function __construct(string $token, ?string $secret = null)
    {
        $query = ['access_token' => $token];

        if ($secret) {
            [$microseconds, $seconds] = explode(' ', microtime());
            $query += [
                'timestamp' => $timestamp = $seconds.floor((float) $microseconds * 1000),
                'sign' => $this->sign($secret, $timestamp),
            ];
        }

        parent::__construct([RequestOptions::QUERY => $query]);
    }

    private function sign(string $secret, string $timestamp): string
    {
        return urlencode(base64_encode(hash_hmac(
            'sha256',
            "$timestamp\n$secret",
            $secret,
            true,
        )));
    }
}
