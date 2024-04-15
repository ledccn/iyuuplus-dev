<?php

namespace Wrench\Socket;

use Wrench\Exception\ConnectionException;

/**
 * Server socket.
 *
 * Used for a server's "master" socket that binds to the configured
 * interface and listens
 */
class ServerSocket extends UriSocket
{
    public const TIMEOUT_ACCEPT = 5;

    /**
     * Whether the socket is listening.
     *
     * @var bool
     */
    protected $listening = false;

    /**
     * Listens.
     *
     * @throws ConnectionException
     */
    public function listen(): void
    {
        $this->socket = \stream_socket_server(
            $this->getUri(),
            $errno,
            $errstr,
            \STREAM_SERVER_BIND | \STREAM_SERVER_LISTEN,
            $this->getStreamContext()
        );

        if (!$this->socket) {
            throw new ConnectionException(\sprintf('Could not listen on socket: %s (%d)', $errstr, $errno));
        }

        $this->listening = true;
    }

    /**
     * Accepts a new connection on the socket.
     *
     * @throws ConnectionException
     *
     * @return resource
     */
    public function accept()
    {
        $new = \stream_socket_accept(
            $this->socket,
            $this->options['timeout_accept']
        );

        if (!$new) {
            throw new ConnectionException(\socket_strerror(\socket_last_error()));
        }

        \socket_set_option(\socket_import_stream($new), \SOL_TCP, \TCP_NODELAY, 1);

        return $new;
    }

    /**
     * Configure the server socket.
     *
     *   Options include:
     *     - backlog               => int, used to limit the number of outstanding
     *                                 connections in the socket's listen queue
     *     - ssl_cert_file         => string, server SSL certificate
     *                                 file location. File should contain
     *                                 certificate and private key
     *     - ssl_passphrase        => string, passphrase for the key
     *     - timeout_accept        => int, seconds, default 5
     */
    protected function configure(array $options): void
    {
        $options = \array_merge([
            'backlog' => 50,
            'ssl_cert_file' => null,
            'ssl_passphrase' => null,
            'ssl_allow_self_signed' => false,
            'timeout_accept' => self::TIMEOUT_ACCEPT,
        ], $options);

        parent::configure($options);
    }

    protected function getSocketStreamContextOptions(): array
    {
        $options = [];

        if ($this->options['backlog']) {
            $options['backlog'] = $this->options['backlog'];
        }

        return $options;
    }

    protected function getSslStreamContextOptions(): array
    {
        $options = [];

        // BC: use server_ssl_local_cert (old value: server_ssl_cert_file)
        if (!empty($this->options['server_ssl_cert_file'])) {
            $options['local_cert'] = $this->options['server_ssl_cert_file'];
        }

        // Otherwise map any options through
        foreach ($this->options as $option => $value) {
            if (\preg_match('/^server_ssl_(.*)$/', $option, $matches)) {
                $options[$matches[1]] = $value;
            }
        }

        return $options;
    }
}
