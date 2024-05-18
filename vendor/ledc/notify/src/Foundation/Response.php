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

use Guanguans\Notify\Foundation\Concerns\DeterminesStatusCode;
use Guanguans\Notify\Foundation\Concerns\Dumpable;
use Guanguans\Notify\Foundation\Exceptions\InvalidArgumentException;
use Guanguans\Notify\Foundation\Exceptions\LogicException;
use Guanguans\Notify\Foundation\Exceptions\RequestException;
use Guanguans\Notify\Foundation\Exceptions\RuntimeException;
use Guanguans\Notify\Foundation\Support\Arr;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Collection;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @template-implements \ArrayAccess<string, mixed>
 *
 * @see https://github.com/laravel/framework
 * @see https://github.com/saloonphp/saloon
 * @see https://github.com/w7corp/easywechat
 */
class Response extends \GuzzleHttp\Psr7\Response implements \ArrayAccess
{
    use DeterminesStatusCode;
    use Dumpable;

    /**
     * The request that generated response.
     *
     * @readonly
     */
    protected ?RequestInterface $request = null;

    /**
     * The request cookies.
     *
     * @readonly
     */
    protected ?CookieJar $cookies = null;

    /**
     * The transfer stats for the request.
     *
     * @readonly
     */
    protected ?TransferStats $transferStats = null;

    /** The decoded JSON response. */
    protected mixed $decoded = null;

    /**
     * Provide debug information about the response.
     */
    public function __debugInfo(): array
    {
        return $this->mergeDebugInfo([
            'handlerStats' => $this->handlerStats(),
            'requestSummary' => $this->request instanceof RequestInterface ? Message::toString($this->request) : null,
            'responseSummary' => Message::toString($this),
            // 'headers' => Arr::map(
            //     $this->headers(),
            //     fn (array $header, string $name): string => $this->getHeaderLine($name),
            // ),
            // 'status' => $this->status(),
            // 'reason' => $this->reason(),
            // 'body' => $this->body(),
            'decodedBody' => $this->json(),
        ]);
    }

    /**
     * Get the body of the response.
     */
    public function __toString(): string
    {
        return $this->body();
    }

    /**
     * Create a instance from a PSR response.
     */
    public static function fromPsrResponse(ResponseInterface $response): self
    {
        return $response instanceof static ? $response : new static(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase(),
        );
    }

    /**
     * Get the body of the response.
     *
     * @noinspection ToStringCallInspection
     */
    public function body(): string
    {
        return (string) $this->getBody();
    }

    /**
     * Get the JSON decoded body of the response as an array or scalar value.
     */
    public function json(mixed $key = null, mixed $default = null): mixed
    {
        if (null === $this->decoded) {
            $this->decoded = json_decode($this->body(), true);
        }

        if (null === $key) {
            return $this->decoded;
        }

        return Arr::get($this->decoded, $key, $default);
    }

    /**
     * Alias of json().
     */
    public function array(mixed $key = null, mixed $default = null)
    {
        return $this->json($key, $default);
    }

    /**
     * Get the JSON decoded body of the response as an object.
     *
     * @noinspection JsonEncodingApiUsageInspection
     */
    public function object(): ?object
    {
        return json_decode($this->body());
    }

    /**
     * Convert the XML response into a \SimpleXMLElement.
     *
     * Suitable for reading small, simple XML responses but not suitable for
     * more advanced XML responses with namespaces and prefixes. Consider
     * using the xmlReader method instead for better compatability.
     *
     * @see https://www.php.net/manual/en/book.simplexml.php
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     *
     * @return false|\SimpleXMLElement
     */
    public function xml(...$arguments): bool|\SimpleXMLElement
    {
        return simplexml_load_string($this->body(), ...$arguments);
    }

    /**
     * Get the JSON decoded body of the response as a collection.
     *
     * Requires Laravel Collections (composer require illuminate/collections)
     *
     * @see https://github.com/illuminate/collections
     *
     * @param null|array-key $key
     */
    public function collect($key = null): Collection
    {
        if (!class_exists(Collection::class)) {
            throw new RuntimeException('To use the "collect" method you must install the illuminate/collections package.');
        }

        return Collection::make($this->json($key));
    }

    /**
     * Generate a data URL from the content type and body.
     */
    public function dataUrl(): string
    {
        return sprintf('data:%s;base64,%s', $this->getHeaderLine('Content-Type'), base64_encode($this->body()));
    }

    /**
     * Save the response to resource or file.
     *
     * @param resource|string $resourceOrPath
     */
    public function saveAs($resourceOrPath, bool $closeResource = true): void
    {
        if (!\is_string($resourceOrPath) && !\is_resource($resourceOrPath)) {
            throw new InvalidArgumentException('The $resourceOrPath argument must be either a file path or a resource.');
        }

        $resource = \is_string($resourceOrPath) ? fopen($resourceOrPath, 'wb+') : $resourceOrPath;

        if (false === $resource) {
            throw new LogicException('Unable to open the resource.');
        }

        rewind($resource);

        $stream = $this->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (!$stream->eof()) {
            fwrite($resource, $stream->read(1024));
        }

        rewind($resource);

        if ($closeResource) {
            fclose($resource);
        }
    }

    /**
     * Check if the content type matches the given type.
     *
     * @noinspection MultipleReturnStatementsInspection
     */
    public function is(string $type): bool
    {
        $contentType = $this->getHeaderLine('Content-Type');

        switch (strtolower($type)) {
            case 'json':
                return false !== strpos($contentType, '/json');
            case 'xml':
                return false !== strpos($contentType, '/xml');
            case 'html':
                return false !== strpos($contentType, '/html');
            case 'image':
                return false !== strpos($contentType, 'image/');
            case 'audio':
                return false !== strpos($contentType, 'audio/');
            case 'video':
                return false !== strpos($contentType, 'video/');
            case 'text':
                return false !== strpos($contentType, 'text/')
                    || false !== strpos($contentType, '/json')
                    || false !== strpos($contentType, '/xml');
            default:
                return false;
        }
    }

    /**
     * Get a header from the response.
     */
    public function header(string $header): string
    {
        return $this->getHeaderLine($header);
    }

    /**
     * Get the headers from the response.
     */
    public function headers(): array
    {
        return $this->getHeaders();
    }

    /**
     * Get the status code of the response.
     */
    public function status(): int
    {
        return $this->getStatusCode();
    }

    /**
     * Get the reason phrase of the response.
     */
    public function reason(): string
    {
        return $this->getReasonPhrase();
    }

    /**
     * Get the effective URI of the response.
     */
    public function effectiveUri(): ?UriInterface
    {
        return $this->transferStats instanceof TransferStats ? $this->transferStats->getEffectiveUri() : null;
    }

    /**
     * Determine if the request was successful.
     */
    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Determine if the response was a redirect.
     */
    public function redirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Determine if the response indicates a client or server error occurred.
     */
    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * Determine if the response indicates a client error occurred.
     */
    public function clientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Determine if the response indicates a server error occurred.
     */
    public function serverError(): bool
    {
        return $this->status() >= 500;
    }

    /**
     * Execute the given callback if there was a server or client error.
     */
    public function onError(callable $callback): self
    {
        if ($this->failed()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Get or set the request that generated response.
     */
    public function request(?RequestInterface $request = null): ?RequestInterface
    {
        if (0 === \func_num_args()) {
            return $this->request;
        }

        if ($this->request instanceof RequestInterface) {
            throw new LogicException('Request already set on the response.');
        }

        return $this->request = $request;
    }

    /**
     * Get or set the response cookies.
     */
    public function cookies(?CookieJar $cookieJar = null): ?CookieJar
    {
        if (0 === \func_num_args()) {
            return $this->cookies;
        }

        if ($this->cookies instanceof CookieJar) {
            throw new LogicException('Cookies already set on the response.');
        }

        return $this->cookies = $cookieJar;
    }

    /**
     * Get or set the transfer stats of the response.
     */
    public function transferStats(?TransferStats $transferStats = null): ?TransferStats
    {
        if (0 === \func_num_args()) {
            return $this->transferStats;
        }

        if ($this->transferStats instanceof TransferStats) {
            throw new LogicException('Transfer stats already set on the response.');
        }

        return $this->transferStats = $transferStats;
    }

    /**
     * Get the handler stats of the response.
     */
    public function handlerStats(): ?array
    {
        return $this->transferStats instanceof TransferStats ? $this->transferStats->getHandlerStats() : null;
    }

    /**
     * Close the stream and any underlying resources.
     */
    public function close(): self
    {
        $this->getBody()->close();

        return $this;
    }

    /**
     * Create an exception if a server or client error occurred.
     */
    public function toException(): ?RequestException
    {
        return $this->failed() ? RequestException::create($this->request, $this) : null;
    }

    /**
     * Throw an exception if a server or client error occurred.
     *
     * @throws RequestException
     */
    public function throw(): self
    {
        $callback = \func_get_args()[0] ?? null;

        if ($this->failed()) {
            throw tap($this->toException(), function ($exception) use ($callback): void {
                if ($callback && \is_callable($callback)) {
                    $callback($this, $exception);
                }
            });
        }

        return $this;
    }

    /**
     * Throw an exception if a server or client error occurred and the given condition evaluates to true.
     *
     * @throws RequestException
     */
    public function throwIf(bool|\Closure $condition): self
    {
        return value($condition, $this) ? $this->throw(\func_get_args()[1] ?? null) : $this;
    }

    /**
     * Throw an exception if the response status code matches the given code.
     *
     * @throws RequestException
     */
    public function throwIfStatus(callable|int $statusCode): self
    {
        if (\is_callable($statusCode) && $statusCode($this->status(), $this)) {
            return $this->throw();
        }

        return $this->status() === $statusCode ? $this->throw() : $this;
    }

    /**
     * Throw an exception unless the response status code matches the given code.
     *
     * @throws RequestException
     */
    public function throwUnlessStatus(callable|int $statusCode): self
    {
        if (\is_callable($statusCode)) {
            return $statusCode($this->status(), $this) ? $this : $this->throw();
        }

        return $this->status() === $statusCode ? $this : $this->throw();
    }

    /**
     * Throw an exception if the response status code is a 4xx level code.
     *
     * @throws RequestException
     */
    public function throwIfClientError(): self
    {
        return $this->clientError() ? $this->throw() : $this;
    }

    /**
     * Throw an exception if the response status code is a 5xx level code.
     *
     * @throws RequestException
     */
    public function throwIfServerError(): self
    {
        return $this->serverError() ? $this->throw() : $this;
    }

    /**
     * Determine if the given offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->json()[$offset]);
    }

    /**
     * Get the value for a given offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->json()[$offset] ?? null;
    }

    /**
     * Set the value at the given offset.
     *
     * @throws \LogicException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @throws \LogicException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }
}
