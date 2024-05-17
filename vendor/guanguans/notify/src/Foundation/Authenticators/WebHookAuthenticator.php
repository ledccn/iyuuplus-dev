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

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;

class WebHookAuthenticator extends NullAuthenticator
{
    private string $webHook;
    private UriFactoryInterface $uriFactory;

    public function __construct(string $webHook, ?UriFactoryInterface $uriFactory = null)
    {
        $this->webHook = $webHook;
        $this->uriFactory = $uriFactory ?? new HttpFactory;
    }

    public function applyToRequest(RequestInterface $request): RequestInterface
    {
        return $request->withUri($this->uriFactory->createUri($this->webHook));
    }
}
