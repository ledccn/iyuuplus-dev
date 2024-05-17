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

namespace Guanguans\Notify\Foundation;

use Guanguans\Notify\Foundation\Authenticators\NullAuthenticator;
use Guanguans\Notify\Foundation\Concerns\Dumpable;
use Guanguans\Notify\Foundation\Concerns\HasHttpClient;
use Guanguans\Notify\Foundation\Contracts\Authenticator;
use Guanguans\Notify\Foundation\Contracts\Message;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class Client implements Contracts\Client
{
    use Dumpable;
    use HasHttpClient;
    private Authenticator $authenticator;

    public function __construct(?Authenticator $authenticator = null)
    {
        $this->authenticator = $authenticator ?? new NullAuthenticator;
    }

    public function __debugInfo(): array
    {
        return $this->mergeDebugInfo([
            'httpClient' => $this->getHttpClient(),
            'httpClientResolver' => $this->getHttpClientResolver(),
            'handlerStack' => $this->getHandlerStack(),
            'httpOptions' => $this->getHttpOptions(),
        ]);
    }

    /**
     * @throws GuzzleException
     *
     * @return Response|ResponseInterface
     */
    public function send(Message $message): ResponseInterface
    {
        return $this->getHttpClient()->request(
            $message->toHttpMethod(),
            $message->toHttpUri(),
            $this->normalizeHttpOptions($this->authenticator->applyToOptions($message->toHttpOptions())),
        );
    }
}
