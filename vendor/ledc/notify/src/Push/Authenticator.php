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

namespace Guanguans\Notify\Push;

use Guanguans\Notify\Foundation\Authenticators\AggregateAuthenticator;
use Guanguans\Notify\Foundation\Authenticators\OptionsAuthenticator;
use Guanguans\Notify\Foundation\Authenticators\TokenUriTemplateAuthenticator;
use GuzzleHttp\RequestOptions;

class Authenticator extends AggregateAuthenticator
{
    public function __construct(string $apiKey)
    {
        parent::__construct(
            new TokenUriTemplateAuthenticator($apiKey),
            new OptionsAuthenticator([RequestOptions::HEADERS => ['X-Api-Key' => $apiKey]]),
        );
    }
}
