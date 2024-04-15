<?php

namespace Wrench\Socket;

use InvalidArgumentException;
use Wrench\Exception\SocketException;
use Wrench\ResourceInterface;
use Wrench\Util\Configurable;

/**
 * Socket class
 * Implements low level logic for connecting, serving, reading to, and
 * writing from WebSocket connections using PHP's streams.
 * Unlike in previous versions of this library, a Socket instance now
 * represents a single underlying socket resource. It's designed to be used
 * by aggregation, rather than inheritance.
 */
abstract class AbstractSocket extends Configurable implements ResourceInterface
{
    /**
     * Default timeout for socket operations (reads, writes).
     *
     * @var int seconds
     */
    public const TIMEOUT_SOCKET = 5;

    public const DEFAULT_RECEIVE_LENGTH = 1400;

    public const NAME_PART_IP = 0;
    public const NAME_PART_PORT = 1;

    /**
     * @var resource|null
     */
    protected $socket = null;

    /**
     * Stream context.
     */
    protected $context = null;

    /**
     * Whether the socket is connected to a server
     * Note, the connection may not be ready to use, but the socket is
     * connected at least. See $handshaked, and other properties in
     * subclasses.
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * The socket name according to stream_socket_get_name.
     *
     * @var string|null
     */
    protected $name;

    /**
     * Gets the IP address of the socket.
     *
     * @throws \Wrench\Exception\SocketException If the IP address cannot be obtained
     *
     * @return string
     */
    public function getIp(): string
    {
        $name = $this->getName();

        if ($name) {
            return self::getNamePart($name, self::NAME_PART_IP);
        } else {
            throw new SocketException('Cannot get socket IP address');
        }
    }

    /**
     * Gets the name of the socket.
     *
     * @return string|null
     */
    protected function getName(): ?string
    {
        if (null === $this->socket) {
            return null;
        }

        if (!$this->name) {
            $this->name = @\stream_socket_get_name($this->socket, true);
        }

        return $this->name;
    }

    /**
     * Gets part of the name of the socket
     * PHP seems to return IPV6 address/port combos like this:
     *   ::1:1234, where ::1 is the address and 1234 the port
     * So, the part number here is either the last : delimited section (the port)
     * or all the other sections (the whole initial part, the address).
     *
     * @param string $name (from $this->getName() usually)
     * @param int    $part 0 or 1
     *
     * @throws SocketException
     */
    public static function getNamePart(string $name, int $part): string
    {
        if (!$name) {
            throw new InvalidArgumentException('Invalid name');
        }

        $parts = \explode(':', $name);

        if (\count($parts) < 2) {
            throw new SocketException('Could not parse name parts: '.$name);
        }

        if (self::NAME_PART_PORT == $part) {
            return \end($parts);
        } elseif (self::NAME_PART_IP == $part) {
            return \implode(':', \array_slice($parts, 0, -1));
        } else {
            throw new InvalidArgumentException('Invalid name part');
        }
    }

    /**
     * Gets the port of the socket.
     *
     * @throws \Wrench\Exception\SocketException If the port cannot be obtained
     *
     * @return int
     */
    public function getPort(): int
    {
        $name = $this->getName();

        if ($name) {
            return (int) self::getNamePart($name, self::NAME_PART_PORT);
        } else {
            throw new SocketException('Cannot get socket IP address');
        }
    }

    /**
     * Get the last error that occurred on the socket.
     *
     * @return string
     */
    public function getLastError(): string
    {
        if ($this->isConnected() && $this->socket) {
            $err = @\socket_last_error($this->socket);
            if ($err) {
                $err = \socket_strerror($err);
            }
            if (!$err) {
                $err = 'Unknown error';
            }

            return $err;
        } else {
            return 'Not connected';
        }
    }

    /**
     * Whether the socket is currently connected.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * @return resource|null
     */
    public function getResource()
    {
        return $this->socket;
    }

    public function getResourceId(): ?int
    {
        if (null === $this->socket) {
            return null;
        }

        return \get_resource_id($this->socket);
    }

    /**
     * @param string $data Binary data to send down the socket
     *
     * @throws SocketException
     *
     * @return int|null The number of bytes sent or null on error
     */
    public function send(string $data): ?int
    {
        if (!$this->isConnected()) {
            throw new SocketException('Socket is not connected');
        }

        $length = \strlen($data);

        if (0 == $length) {
            return 0;
        }

        for ($i = $length; $i > 0; $i -= $written) {
            $written = @\fwrite($this->socket, \substr($data, -1 * $i));

            if (false === $written) {
                return null;
            } elseif (0 === $written) {
                return null;
            }
        }

        return $length;
    }

    /**
     * Receive data from the socket.
     */
    public function receive(int $length = self::DEFAULT_RECEIVE_LENGTH): string
    {
        $buffer = '';
        $metadata['unread_bytes'] = 0;
        $makeBlockingAfterRead = false;

        try {
            do {
                // feof means socket has been closed
                // also, sometimes in long running processes the system seems to kill the underlying socket
                if (!$this->socket || \feof($this->socket)) {
                    $this->disconnect();

                    return $buffer;
                }

                $result = \fread($this->socket, $length);

                if ($makeBlockingAfterRead) {
                    \stream_set_blocking($this->socket, true);
                    $makeBlockingAfterRead = false;
                }

                if (false === $result) {
                    return $buffer;
                }

                $buffer .= $result;

                // feof means socket has been closed
                if (\feof($this->socket)) {
                    $this->disconnect();

                    return $buffer;
                }

                $continue = false;

                if (1 === \strlen($result)) {
                    // Workaround Chrome behavior (still needed?)
                    $continue = true;
                }

                if (\strlen($result) === $length) {
                    $continue = true;
                }

                // Continue if more data to be read
                $metadata = \stream_get_meta_data($this->socket);

                /** @phpstan-ignore-next-line */
                if (isset($metadata['unread_bytes'])) {
                    if (!$metadata['unread_bytes']) {
                        // stop it, if we read a full message in previous time
                        $continue = false;
                    } else {
                        $continue = true;
                        // it makes sense only if unread_bytes less than DEFAULT_RECEIVE_LENGTH
                        if ($length > $metadata['unread_bytes']) {
                            // http://php.net/manual/en/function.stream-get-meta-data.php
                            // 'unread_bytes' don't describes real length correctly.
                            // $length = $metadata['unread_bytes'];

                            // Socket is a blocking by default. When we do a blocking read from an empty
                            // queue it will block and the server will hang. https://bugs.php.net/bug.php?id=1739
                            \stream_set_blocking($this->socket, false);
                            $makeBlockingAfterRead = true;
                        }
                    }
                }
            } while ($continue);

            return $buffer;
        } finally {
            if ($this->socket && !\feof($this->socket) && $makeBlockingAfterRead) {
                \stream_set_blocking($this->socket, true);
            }
        }
    }

    /**
     * Disconnect the socket.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            \stream_socket_shutdown($this->socket, \STREAM_SHUT_RDWR);
        }
        $this->socket = null;
        $this->connected = false;
    }

    /**
     * Configure options
     * Options include
     *   - timeout_socket       => int, seconds, default 5.
     *
     * @param array $options
     *
     * @return void
     */
    protected function configure(array $options): void
    {
        $options = \array_merge([
            'timeout_socket' => self::TIMEOUT_SOCKET,
        ], $options);

        parent::configure($options);
    }
}
