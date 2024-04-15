<?php

namespace Wrench\Protocol;

use Exception;
use InvalidArgumentException;
use Wrench\Exception\BadRequestException;
use Wrench\Exception\HandshakeException;
use Wrench\Payload\Payload;

/**
 * Definitions and implementation helpers for the Wrenchs protocol.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6455
 */
abstract class Protocol
{
    /**
     * Relevant schemes.
     */
    public const SCHEME_WEBSOCKET = 'ws';
    public const SCHEME_WEBSOCKET_SECURE = 'wss';
    public const SCHEME_UNDERLYING = 'tcp';
    public const SCHEME_UNDERLYING_SECURE = 'tls';

    /**
     * HTTP headers.
     */
    public const HEADER_HOST = 'host';
    public const HEADER_KEY = 'sec-websocket-key';
    public const HEADER_PROTOCOL = 'sec-websocket-protocol';
    public const HEADER_VERSION = 'sec-websocket-version';
    public const HEADER_ACCEPT = 'sec-websocket-accept';
    public const HEADER_EXTENSIONS = 'sec-websocket-extensions';
    public const HEADER_ORIGIN = 'origin';
    public const HEADER_CONNECTION = 'connection';
    public const HEADER_UPGRADE = 'upgrade';

    /**
     * HTTP error statuses.
     */
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_RATE_LIMITED = 420;
    public const HTTP_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;

    /**
     * Close statuses.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6455#section-7.4
     */
    public const CLOSE_NORMAL = 1000;
    public const CLOSE_GOING_AWAY = 1001;
    public const CLOSE_PROTOCOL_ERROR = 1002;
    public const CLOSE_DATA_INVALID = 1003;
    public const CLOSE_RESERVED = 1004;
    public const CLOSE_RESERVED_NONE = 1005;
    public const CLOSE_RESERVED_ABNORM = 1006;
    public const CLOSE_DATA_INCONSISTENT = 1007;
    public const CLOSE_POLICY_VIOLATION = 1008;
    public const CLOSE_MESSAGE_TOO_BIG = 1009;
    public const CLOSE_EXTENSION_NEEDED = 1010;
    public const CLOSE_UNEXPECTED = 1011;
    public const CLOSE_RESERVED_TLS = 1015;

    /**
     * Frame types
     *  %x0 denotes a continuation frame
     *  %x1 denotes a text frame
     *  %x2 denotes a binary frame
     *  %x3-7 are reserved for further non-control frames
     *  %x8 denotes a connection close
     *  %x9 denotes a ping
     *  %xA denotes a pong
     *  %xB-F are reserved for further control frames.
     */
    public const TYPE_CONTINUATION = 0;
    public const TYPE_TEXT = 1;
    public const TYPE_BINARY = 2;
    public const TYPE_RESERVED_3 = 3;
    public const TYPE_RESERVED_4 = 4;
    public const TYPE_RESERVED_5 = 5;
    public const TYPE_RESERVED_6 = 6;
    public const TYPE_RESERVED_7 = 7;
    public const TYPE_CLOSE = 8;
    public const TYPE_PING = 9;
    public const TYPE_PONG = 10;
    public const TYPE_RESERVED_11 = 11;
    public const TYPE_RESERVED_12 = 12;
    public const TYPE_RESERVED_13 = 13;
    public const TYPE_RESERVED_14 = 14;
    public const TYPE_RESERVED_15 = 15;

    /**
     * Used in the WebSocket accept header.
     */
    public const MAGIC_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /**
     * The request MUST contain an |Upgrade| header field whose value
     * MUST include the "websocket" keyword.
     */
    public const UPGRADE_VALUE = 'websocket';

    /**
     * The request MUST contain a |Connection| header field whose value
     * MUST include the "Upgrade" token.
     */
    public const CONNECTION_VALUE = 'Upgrade';

    /**
     * printf compatible, passed request path string.
     */
    public const REQUEST_LINE_FORMAT = 'GET %s HTTP/1.1';

    /**
     * Used for parsing requested path. preg_* compatible.
     */
    public const REQUEST_LINE_REGEX = '/^GET (\S+) HTTP\/1.1$/';

    /**
     * printf compatible.
     */
    public const RESPONSE_LINE_FORMAT = 'HTTP/1.1 %d %s';

    /**
     * printf compatible, passed header name and value.
     */
    public const HEADER_LINE_FORMAT = '%s: %s';

    public const CLOSE_REASONS = [
        self::CLOSE_NORMAL => 'normal close',
        self::CLOSE_GOING_AWAY => 'going away',
        self::CLOSE_PROTOCOL_ERROR => 'protocol error',
        self::CLOSE_DATA_INVALID => 'data invalid',
        self::CLOSE_DATA_INCONSISTENT => 'data inconsistent',
        self::CLOSE_POLICY_VIOLATION => 'policy violation',
        self::CLOSE_MESSAGE_TOO_BIG => 'message too big',
        self::CLOSE_EXTENSION_NEEDED => 'extension needed',
        self::CLOSE_UNEXPECTED => 'unexpected error',
        self::CLOSE_RESERVED => null, // Don't use these!
        self::CLOSE_RESERVED_NONE => null,
        self::CLOSE_RESERVED_ABNORM => null,
        self::CLOSE_RESERVED_TLS => null,
    ];

    public const FRAME_TYPES = [
        'continuation' => self::TYPE_CONTINUATION,
        'text' => self::TYPE_TEXT,
        'binary' => self::TYPE_BINARY,
        'close' => self::TYPE_CLOSE,
        'ping' => self::TYPE_PING,
        'pong' => self::TYPE_PONG,
    ];

    public const HTTP_RESPONSES = [
        self::HTTP_SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::HTTP_BAD_REQUEST => 'Bad Request',
        self::HTTP_UNAUTHORIZED => 'Unauthorized',
        self::HTTP_FORBIDDEN => 'Forbidden',
        self::HTTP_NOT_FOUND => 'Not Found',
        self::HTTP_NOT_IMPLEMENTED => 'Not Implemented',
        self::HTTP_RATE_LIMITED => 'Enhance Your Calm',
    ];

    /**
     * Valid schemes.
     *
     * @var list<string>
     */
    protected static $schemes = [
        self::SCHEME_WEBSOCKET,
        self::SCHEME_WEBSOCKET_SECURE,
        self::SCHEME_UNDERLYING,
        self::SCHEME_UNDERLYING_SECURE,
    ];

    /**
     * Generates a key suitable for use in the protocol.
     *
     * This base implementation returns a 16-byte (128 bit) random key as a
     * binary string.
     */
    public function generateKey(): string
    {
        return \base64_encode(\random_bytes(16));
    }

    /**
     * Gets request handshake string
     *   The leading line from the client follows the Request-Line format.
     *   The leading line from the server follows the Status-Line format.  The
     *   Request-Line and Status-Line productions are defined in [RFC2616].
     *   An unordered set of header fields comes after the leading line in
     *   both cases.  The meaning of these header fields is specified in
     *   Section 4 of this document.  Additional header fields may also be
     *   present, such as cookies [RFC6265].  The format and parsing of
     *   headers is as defined in [RFC2616].
     *
     * @param string $uri    WebSocket URI, e.g. ws://example.org:8000/chat
     * @param string $key    16 byte binary string key
     * @param string $origin Origin of the request
     */
    public function getRequestHandshake(
        string $uri,
        string $key,
        string $origin,
        array $headers = []
    ): string {
        if (!$uri || !$key || !$origin) {
            throw new InvalidArgumentException('You must supply a URI, key and origin');
        }

        [$scheme, $host, $port, $path, $query] = self::validateUri($uri);

        if ($query) {
            $path .= '?'.$query;
        }

        if (self::SCHEME_WEBSOCKET == $scheme && 80 == $port) {
            // do nothing
        } elseif (self::SCHEME_WEBSOCKET_SECURE == $scheme && 443 == $port) {
            // do nothing
        } else {
            $host = $host.':'.$port;
        }

        $handshake = [
            \sprintf(self::REQUEST_LINE_FORMAT, $path),
        ];

        $headers = \array_merge(
            $this->getDefaultRequestHeaders(
                $host,
                $key,
                $origin
            ),
            $headers
        );

        foreach ($headers as $name => $value) {
            $handshake[] = \sprintf(self::HEADER_LINE_FORMAT, $name, $value);
        }

        return \implode("\r\n", $handshake)."\r\n\r\n";
    }

    /**
     * Validates a WebSocket URI.
     *
     * @throws InvalidArgumentException
     */
    public function validateUri(string $uri): array
    {
        if (!$uri) {
            throw new InvalidArgumentException('Invalid URI');
        }

        $scheme = \parse_url($uri, \PHP_URL_SCHEME);
        $this->validateScheme($scheme ?: '');

        $host = \parse_url($uri, \PHP_URL_HOST);
        if (!$host) {
            throw new InvalidArgumentException('Invalid host');
        }

        $port = \parse_url($uri, \PHP_URL_PORT);
        if (!$port) {
            $port = $this->getPort($scheme);
        }

        $path = \parse_url($uri, \PHP_URL_PATH);
        if (!$path) {
            throw new InvalidArgumentException('Invalid path');
        }

        $query = \parse_url($uri, \PHP_URL_QUERY);

        return [$scheme, $host, $port, $path, $query ?: ''];
    }

    /**
     * Validates a scheme.
     *
     * @throws InvalidArgumentException
     */
    protected function validateScheme(string $scheme): string
    {
        if (!$scheme) {
            throw new InvalidArgumentException('No scheme specified');
        }
        if (!\in_array($scheme, self::$schemes)) {
            throw new InvalidArgumentException('Unknown socket scheme: '.$scheme);
        }

        if (self::SCHEME_WEBSOCKET_SECURE == $scheme) {
            return self::SCHEME_UNDERLYING_SECURE;
        }

        return self::SCHEME_UNDERLYING;
    }

    /**
     * Gets the default port for a scheme
     * By default, the WebSocket Protocol uses port 80 for regular WebSocket
     * connections and port 443 for WebSocket connections tunneled over TLS.
     */
    protected function getPort(string $scheme): int
    {
        if (self::SCHEME_WEBSOCKET == $scheme) {
            return 80;
        }

        if (self::SCHEME_WEBSOCKET_SECURE == $scheme) {
            return 443;
        }

        if (self::SCHEME_UNDERLYING == $scheme) {
            return 80;
        }

        if (self::SCHEME_UNDERLYING_SECURE == $scheme) {
            return 443;
        }

        throw new InvalidArgumentException('Unknown websocket scheme');
    }

    /**
     * Gets the default request headers.
     *
     * @return array<string, string>
     */
    protected function getDefaultRequestHeaders(
        string $host,
        string $key,
        string $origin
    ): array {
        return [
            self::HEADER_HOST => $host,
            self::HEADER_UPGRADE => self::UPGRADE_VALUE,
            self::HEADER_CONNECTION => self::CONNECTION_VALUE,
            self::HEADER_KEY => $key,
            self::HEADER_ORIGIN => $origin,
            self::HEADER_VERSION => (string) $this->getVersion(),
        ];
    }

    /**
     * Gets the version number.
     */
    abstract public function getVersion(): int;

    /**
     * Gets a handshake response body.
     */
    public function getResponseHandshake(string $key, array $headers = []): string
    {
        $headers = \array_merge(
            $this->getSuccessResponseHeaders($key),
            $headers
        );

        return $this->getHttpResponse(self::HTTP_SWITCHING_PROTOCOLS, $headers);
    }

    /**
     * Gets the default response headers.
     *
     * @param string $key
     *
     * @return string[]
     */
    protected function getSuccessResponseHeaders(string $key): array
    {
        return [
            self::HEADER_UPGRADE => self::UPGRADE_VALUE,
            self::HEADER_CONNECTION => self::CONNECTION_VALUE,
            self::HEADER_ACCEPT => $this->getAcceptValue($key),
        ];
    }

    /**
     * Gets the expected accept value for a handshake response
     * Note that the protocol calls for the base64 encoded value to be hashed,
     * not the original 16 byte random key.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6455#section-4.2.2
     *
     * @param string $key
     *
     * @return string
     */
    protected function getAcceptValue(string $key): string
    {
        return \base64_encode(\sha1($key.self::MAGIC_GUID, true));
    }

    /**
     * Gets an HTTP response.
     */
    protected function getHttpResponse(int $status, array $headers = []): string
    {
        if (\array_key_exists($status, self::HTTP_RESPONSES)) {
            $response = self::HTTP_RESPONSES[$status];
        } else {
            $response = self::HTTP_RESPONSES[self::HTTP_NOT_IMPLEMENTED];
        }

        $handshake = [
            \sprintf(self::RESPONSE_LINE_FORMAT, $status, $response),
        ];

        foreach ($headers as $name => $value) {
            $handshake[] = \sprintf(self::HEADER_LINE_FORMAT, $name, $value);
        }

        return \implode("\r\n", $handshake)."\r\n\r\n";
    }

    /**
     * Gets a response to an error in the handshake.
     *
     * @param int|Exception $e       Exception or HTTP error
     * @param array         $headers
     *
     * @return string
     */
    public function getResponseError($e, array $headers = []): string
    {
        $code = false;

        if ($e instanceof Exception) {
            $code = $e->getCode();
        } elseif (\is_numeric($e)) {
            $code = (int) $e;
        }

        if (!$code || $code < 400 || $code > 599) {
            $code = self::HTTP_SERVER_ERROR;
        }

        return $this->getHttpResponse($code, $headers);
    }

    /**
     * @throws HandshakeException
     */
    public function validateResponseHandshake(string $response, string $key): bool
    {
        if (!$response) {
            return false;
        }

        $statusCode = $this->getStatusCode($response);

        if (self::HTTP_SWITCHING_PROTOCOLS !== $statusCode) {
            $errorMessage = \explode("\n", \trim($this->getBody($response)), 2)[0];

            throw new HandshakeException(\trim(\sprintf('Expected handshake response status code %d, but received %d. %s', self::HTTP_SWITCHING_PROTOCOLS, $statusCode, $errorMessage)));
        }

        $acceptHeaderValue = $this->getHeaders($response)[self::HEADER_ACCEPT] ?? '';

        if ('' === $acceptHeaderValue) {
            throw new HandshakeException('No accept header received on handshake response');
        }

        return $this->getEncodedHash($key) === $acceptHeaderValue;
    }

    /**
     * Gets the status code from a full response.
     *
     * If there is no status line, we return 0.
     *
     * @return int
     */
    protected function getStatusCode(string $response): int
    {
        [$statusLine] = \explode("\r\n", $response, 2);

        [$protocol, $statusCode] = \explode(' ', $response, 2);

        return (int) $statusCode;
    }

    /**
     * Gets the headers from a full response.
     *
     * @return array<string, array>
     */
    protected function getHeaders(string $response): array
    {
        $parts = \explode("\r\n\r\n", $response, 2);

        if (\count($parts) < 2) {
            $parts[] = '';
        }

        [$headers, $body] = $parts;

        $return = [];
        foreach (\explode("\r\n", $headers) as $header) {
            $parts = \explode(':', $header, 2);
            if (2 == \count($parts)) {
                [$name, $value] = $parts;
                if (!isset($return[$name])) {
                    $return[$name] = \trim($value);
                } else {
                    if (\is_array($return[$name])) {
                        $return[$name][] = \trim($value);
                    } else {
                        $return[$name] = [$return[$name], \trim($value)];
                    }
                }
            }
        }

        return \array_change_key_case($return);
    }

    /**
     * Gets the body from a full response.
     *
     * @return string
     */
    protected function getBody(string $response): string
    {
        return \explode("\r\n\r\n", $response, 2)[1] ?? '';
    }

    /**
     * Gets an encoded hash for a key.
     *
     * @param string $key
     *
     * @return string
     */
    public function getEncodedHash(string $key): string
    {
        return \base64_encode(\pack('H*', \sha1($key.self::MAGIC_GUID)));
    }

    /**
     * Validates a request handshake.
     *
     * @param string $request
     *
     * @throws BadRequestException
     */
    public function validateRequestHandshake(string $request): array
    {
        [$request, $headers] = $this->getRequestHeaders($request);
        // make a copy of the headers array to store all extra headers
        $extraHeaders = $headers;

        // parse the resulting url to separate query parameters from the path
        $url = \parse_url($this->validateRequestLine($request));
        $path = $url['path'] ?? null;
        $urlParams = [];
        if (isset($url['query'])) {
            \parse_str($url['query'], $urlParams);
        }

        if (empty($headers[self::HEADER_ORIGIN])) {
            throw new BadRequestException('No origin header');
        } else {
            unset($extraHeaders[self::HEADER_ORIGIN]);
        }

        $origin = $headers[self::HEADER_ORIGIN];

        if (!isset($headers[self::HEADER_UPGRADE])
            || self::UPGRADE_VALUE != \strtolower($headers[self::HEADER_UPGRADE])
        ) {
            throw new BadRequestException('Invalid upgrade header');
        } else {
            unset($extraHeaders[self::HEADER_UPGRADE]);
        }

        if (!isset($headers[self::HEADER_CONNECTION])
            || false === \stripos($headers[self::HEADER_CONNECTION], self::CONNECTION_VALUE)
        ) {
            throw new BadRequestException('Invalid connection header');
        } else {
            unset($extraHeaders[self::HEADER_CONNECTION]);
        }

        if (!isset($headers[self::HEADER_HOST])) {
            // @todo Validate host == listening socket? Or would that break
            //        TCP proxies?
            throw new BadRequestException('No host header');
        } else {
            unset($extraHeaders[self::HEADER_HOST]);
        }

        if (!isset($headers[self::HEADER_VERSION])) {
            throw new BadRequestException('No version header received on handshake request');
        }

        if (!$this->acceptsVersion((int) $headers[self::HEADER_VERSION])) {
            throw new BadRequestException('Unsupported version: '.$headers[self::HEADER_VERSION]);
        } else {
            unset($extraHeaders[self::HEADER_VERSION]);
        }

        if (!isset($headers[self::HEADER_KEY])) {
            throw new BadRequestException('No key header received');
        }

        $key = \trim($headers[self::HEADER_KEY]);

        if (!$key) {
            throw new BadRequestException('Invalid key');
        } else {
            unset($extraHeaders[self::HEADER_KEY]);
        }

        // Optional
        $protocol = null;
        if (isset($headers[self::HEADER_PROTOCOL])) {
            $protocol = $headers[self::HEADER_PROTOCOL];
            unset($extraHeaders[self::HEADER_PROTOCOL]);
        }

        $extensions = [];
        if (!empty($headers[self::HEADER_EXTENSIONS])) {
            $extensions = $headers[self::HEADER_EXTENSIONS];
            if (\is_scalar($extensions)) {
                $extensions = [$extensions];
            }
        }
        unset($extraHeaders[self::HEADER_EXTENSIONS]);

        return [$path, $origin, $key, $extensions, $protocol, $extraHeaders, $urlParams];
    }

    /**
     * Gets request headers.
     *
     * @throws InvalidArgumentException
     */
    protected function getRequestHeaders(string $response): array
    {
        $eol = \stripos($response, "\r\n");

        if (false === $eol) {
            throw new InvalidArgumentException('Invalid request line');
        }

        $request = \substr($response, 0, $eol);
        $headers = $this->getHeaders(\substr($response, $eol + 2));

        return [$request, $headers];
    }

    /**
     * Validates a request line.
     *
     * @throws BadRequestException
     */
    protected function validateRequestLine(string $line): string
    {
        \preg_match(self::REQUEST_LINE_REGEX, $line, $matches);

        if (!($matches[1] ?? false)) {
            throw new BadRequestException('Invalid request line', 400);
        }

        /** @var string */
        return $matches[1];
    }

    /**
     * Subclasses should implement this method and return a boolean to the given
     * version string, as to whether they would like to accept requests from
     * user agents that specify that version.
     */
    abstract public function acceptsVersion(int $version): bool;

    /**
     * Gets a suitable WebSocket close frame.
     *
     * Set `masked` to false if you send a close frame from server side.
     */
    public function getClosePayload(int $code, bool $masked = true): Payload
    {
        if (!\array_key_exists($code, self::CLOSE_REASONS)) {
            $code = self::CLOSE_UNEXPECTED;
        }

        $body = \pack('n', $code).self::CLOSE_REASONS[$code];

        $payload = $this->getPayload();

        return $payload->encode($body, self::TYPE_CLOSE, $masked);
    }

    /**
     * Gets a payload instance, suitable for use in decoding/encoding protocol
     * frames.
     */
    abstract public function getPayload(): Payload;

    /**
     * Validates a socket URI.
     *
     * @throws InvalidArgumentException
     */
    public function validateSocketUri(string $uri): array
    {
        if (!$uri) {
            throw new InvalidArgumentException('Invalid URI');
        }

        $scheme = $this->validateScheme(\parse_url($uri, \PHP_URL_SCHEME) ?: '');

        $host = \parse_url($uri, \PHP_URL_HOST);
        if (!$host) {
            throw new InvalidArgumentException('Invalid host');
        }

        $port = \parse_url($uri, \PHP_URL_PORT);
        if (!$port) {
            $port = $this->getPort($scheme);
        }

        return [$scheme, $host, $port];
    }

    /**
     * Validates an origin URI.
     *
     * @throws InvalidArgumentException
     */
    public function validateOriginUri(string $origin): string
    {
        $origin = (string) $origin;
        if (!$origin) {
            throw new InvalidArgumentException('Invalid URI');
        }

        $scheme = \parse_url($origin, \PHP_URL_SCHEME);
        if (!$scheme) {
            throw new InvalidArgumentException('Invalid scheme');
        }

        $host = \parse_url($origin, \PHP_URL_HOST);
        if (!$host) {
            throw new InvalidArgumentException('Invalid host');
        }

        return $origin;
    }
}
