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

namespace Guanguans\Notify\Foundation\Concerns;

/**
 * @mixin \Guanguans\Notify\Foundation\Response
 */
trait DeterminesStatusCode
{
    /**
     * Determine if the response code was 200 "OK" response.
     */
    public function ok(): bool
    {
        return 200 === $this->status();
    }

    /**
     * Determine if the response code was 201 "Created" response.
     */
    public function created(): bool
    {
        return 201 === $this->status();
    }

    /**
     * Determine if the response code was 202 "Accepted" response.
     */
    public function accepted(): bool
    {
        return 202 === $this->status();
    }

    /**
     * Determine if the response code was the given status code and the body has no content.
     */
    public function noContent(int $status = 204): bool
    {
        return $this->status() === $status && '' === $this->body();
    }

    /**
     * Determine if the response code was a 301 "Moved Permanently".
     */
    public function movedPermanently(): bool
    {
        return 301 === $this->status();
    }

    /**
     * Determine if the response code was a 302 "Found" response.
     */
    public function found(): bool
    {
        return 302 === $this->status();
    }

    /**
     * Determine if the response code was a 304 "Not Modified" response.
     */
    public function notModified(): bool
    {
        return 304 === $this->status();
    }

    /**
     * Determine if the response was a 400 "Bad Request" response.
     */
    public function badRequest(): bool
    {
        return 400 === $this->status();
    }

    /**
     * Determine if the response was a 401 "Unauthorized" response.
     */
    public function unauthorized(): bool
    {
        return 401 === $this->status();
    }

    /**
     * Determine if the response was a 402 "Payment Required" response.
     */
    public function paymentRequired(): bool
    {
        return 402 === $this->status();
    }

    /**
     * Determine if the response was a 403 "Forbidden" response.
     */
    public function forbidden(): bool
    {
        return 403 === $this->status();
    }

    /**
     * Determine if the response was a 404 "Not Found" response.
     */
    public function notFound(): bool
    {
        return 404 === $this->status();
    }

    /**
     * Determine if the response was a 408 "Request Timeout" response.
     */
    public function requestTimeout(): bool
    {
        return 408 === $this->status();
    }

    /**
     * Determine if the response was a 409 "Conflict" response.
     */
    public function conflict(): bool
    {
        return 409 === $this->status();
    }

    /**
     * Determine if the response was a 422 "Unprocessable Entity" response.
     */
    public function unprocessableEntity(): bool
    {
        return 422 === $this->status();
    }

    /**
     * Determine if the response was a 429 "Too Many Requests" response.
     */
    public function tooManyRequests(): bool
    {
        return 429 === $this->status();
    }
}
