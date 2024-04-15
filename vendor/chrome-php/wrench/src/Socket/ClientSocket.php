<?php

namespace Wrench\Socket;

/**
 * Options:
 *  - timeout_connect      => int, seconds, default 2.
 */
class ClientSocket extends UriSocket
{
    /**
     * Default connection timeout.
     *
     * @var int seconds
     */
    public const TIMEOUT_CONNECT = 2;

    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * Connects to the given socket.
     */
    public function connect(): bool
    {
        if ($this->isConnected()) {
            return true;
        }

        $errno = null;
        $errstr = null;

        // Supress PHP error, we're handling it
        $this->socket = @\stream_socket_client(
            $this->getUri(),
            $errno,
            $errstr,
            $this->options['timeout_connect'],
            \STREAM_CLIENT_CONNECT,
            $this->getStreamContext()
        );

        if (!$this->socket) {
            throw new \Wrench\Exception\ConnectionException(\sprintf('Could not connect to socket: %s (%d)', $errstr, $errno));
        }

        \stream_set_timeout($this->socket, $this->options['timeout_socket']);

        return $this->connected = true;
    }

    /**
     * Configure the client socket.
     *
     * Options include:
     *
     *     - ssl_verify_peer       => boolean, whether to perform peer verification
     *                                 of SSL certificate used
     *     - ssl_allow_self_signed => boolean, whether ssl_verify_peer allows
     *                                 self-signed certs
     *     - timeout_connect       => int, seconds, default 2
     *
     * @param array $options
     */
    protected function configure(array $options): void
    {
        $options = \array_merge([
            'timeout_connect' => self::TIMEOUT_CONNECT,
            'ssl_verify_peer' => false,
            'ssl_allow_self_signed' => true,
        ], $options);

        parent::configure($options);
    }

    protected function getSocketStreamContextOptions(): array
    {
        $options = [];

        return $options;
    }

    protected function getSslStreamContextOptions(): array
    {
        $options = [];

        if ($this->options['ssl_verify_peer']) {
            $options['verify_peer'] = true;
        }

        if ($this->options['ssl_allow_self_signed']) {
            $options['allow_self_signed'] = true;
        }

        return $options;
    }
}
