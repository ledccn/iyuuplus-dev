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
use GuzzleHttp\UriTemplate\UriTemplate;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * @see https://github.com/guzzle/uri-template
 * @see https://github.com/rize/UriTemplate
 */
class UriTemplateAuthenticator extends NullAuthenticator
{
    private array $variables;
    private UriFactoryInterface $uriFactory;

    public function __construct(array $variables, ?UriFactoryInterface $uriFactory = null)
    {
        $this->variables = $variables;
        $this->uriFactory = $uriFactory ?? new HttpFactory;
    }

    /**
     * @noinspection ToStringCallInspection
     */
    public function applyToRequest(RequestInterface $request): RequestInterface
    {
        return $request->withUri(
            $this->uriFactory->createUri(
                UriTemplate::expand(urldecode((string) $request->getUri()), $this->variables),
            ),
        );
    }
}
