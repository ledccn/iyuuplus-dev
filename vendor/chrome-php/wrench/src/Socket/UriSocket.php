<?php

namespace Wrench\Socket;

use Wrench\Protocol\Protocol;

abstract class UriSocket extends AbstractSocket
{
    protected $scheme;
    protected $host;
    protected $port;

    /**
     * @param string $uri     WebSocket URI, e.g. ws://example.org:8000/chat
     * @param array  $options (optional)
     *                        Options:
     *                        - protocol             => Wrench\Protocol object, latest protocol
     *                        version used if not specified
     *                        - timeout_socket       => int, seconds, default 5
     *                        - server_ssl_cert_file => string, server SSL certificate
     *                        file location. File should contain
     *                        certificate and private key
     *                        - server_ssl_passphrase => string, passphrase for the key
     *                        - server_ssl_allow_self_signed => boolean, whether to allows self-
     *                        signed certs
     */
    public function __construct(string $uri, array $options = [])
    {
        parent::__construct($options);

        [$this->scheme, $this->host, $this->port]
            = $this->protocol->validateSocketUri($uri);
    }

    /**
     * Gets the host name.
     */
    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @todo DNS lookup? Override getIp()?
     */
    protected function getName(): string
    {
        return \sprintf('%s:%s', $this->host, $this->port);
    }

    /**
     * Gets the canonical/normalized URI for this socket.
     */
    protected function getUri(): string
    {
        return \sprintf(
            '%s://%s:%d',
            $this->scheme,
            $this->host,
            $this->port
        );
    }

    /**
     * Gets a stream context.
     *
     * @return resource
     */
    protected function getStreamContext(bool $listen = false)
    {
        $options = [];

        if (Protocol::SCHEME_UNDERLYING_SECURE == $this->scheme
            || Protocol::SCHEME_UNDERLYING == $this->scheme
        ) {
            $options['socket'] = $this->getSocketStreamContextOptions();
        }

        if (Protocol::SCHEME_UNDERLYING_SECURE == $this->scheme) {
            $options['ssl'] = $this->getSslStreamContextOptions();
        }

        return \stream_context_create(
            $options,
            []
        );
    }

    /**
     * Returns an array of socket stream context options.
     *
     * See http://php.net/manual/en/context.socket.php.
     */
    abstract protected function getSocketStreamContextOptions(): array;

    /**
     * Returns an array of ssl stream context options.
     *
     * See http://php.net/manual/en/context.ssl.php.
     */
    abstract protected function getSslStreamContextOptions(): array;
}
