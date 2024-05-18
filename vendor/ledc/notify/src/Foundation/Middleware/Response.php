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

use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Response
{
    private static ?TransferStats $transferStats = null;

    public function __invoke(callable $handler): callable
    {
        return static fn (RequestInterface $request, array $options) => $handler($request, $options)->then(
            static function (ResponseInterface $response) use ($request, $options): ResponseInterface {
                $response = \Guanguans\Notify\Foundation\Response::fromPsrResponse($response);

                $response->request($request);
                $response->cookies($options[RequestOptions::COOKIES] ?: null);
                $response->transferStats(self::$transferStats);

                return $response;
            },
        );
    }

    public static function setTransferStats(TransferStats $transferStats): void
    {
        self::$transferStats = $transferStats;
    }
}
