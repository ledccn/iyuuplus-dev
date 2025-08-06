<?php

namespace Wrench;

use InvalidArgumentException;
use Wrench\Exception\FrameException;
use Wrench\Exception\HandshakeException;
use Wrench\Exception\SocketException;
use Wrench\Payload\Payload;
use Wrench\Payload\PayloadHandler;
use Wrench\Protocol\Protocol;
use Wrench\Socket\ClientSocket;
use Wrench\Util\Configurable;

/**
 * Client class.
 *
 * Represents a websocket client
 */
class Client extends Configurable
{
    /**
     * @var int bytes
     */
    public const MAX_HANDSHAKE_RESPONSE = 1500;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $origin;

    /**
     * @var ClientSocket|null
     */
    protected $socket;

    /**
     * Request headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Whether the client is connected.
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * @var PayloadHandler|null
     */
    protected $payloadHandler = null;

    /**
     * Complete received payloads.
     *
     * @var array<Payload>
     */
    protected $received = [];

    /**
     * @param string $origin  The origin to include in the handshake (required
     *                        in later versions of the protocol)
     * @param array  $options (optional) Array of options
     *                        - socket   => AbstractSocket instance (otherwise created)
     *                        - protocol => Protocol
     */
    public function __construct(string $uri, string $origin, array $options = [])
    {
        parent::__construct($options);

        if (!$uri) {
            throw new InvalidArgumentException('No URI specified');
        }
        $this->uri = $uri;

        if (!$origin) {
            throw new InvalidArgumentException('No origin specified');
        }
        $this->origin = $origin;

        $this->protocol->validateUri($this->uri);
        $this->protocol->validateOriginUri($this->origin);

        $this->configureSocket();
        $this->configurePayloadHandler();
    }

    /**
     * Configures the client socket.
     */
    protected function configureSocket(): void
    {
        $class = $this->options['socket_class'];
        $options = $this->options['socket_options'];
        $this->socket = new $class($this->uri, $options);
    }

    /**
     * Configures the payload handler.
     */
    protected function configurePayloadHandler(): void
    {
        $this->payloadHandler = new PayloadHandler([$this, 'onData'], $this->options);
    }

    /**
     * Payload receiver
     * Public because called from our PayloadHandler. Don't call us, we'll call
     * you (via the on_data_callback option).
     *
     * @param Payload $payload
     */
    public function onData(Payload $payload): void
    {
        $this->received[] = $payload;
        if ($callback = $this->options['on_data_callback']) {
            \call_user_func($callback, $payload);
        }
    }

    /**
     * Adds a request header to be included in the initial handshake.
     *
     * For example, to include a Cookie header.
     *
     * @return void
     */
    public function addRequestHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Sends data to the socket.
     *
     * @param int $type See Protocol::TYPE_*
     *
     * @return bool Success
     */
    public function sendData(string $data, int $type = Protocol::TYPE_TEXT, bool $masked = true): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        $payload = $this->protocol->getPayload();

        $payload->encode(
            $data,
            $type,
            $masked
        );

        return $payload->sendToSocket($this->socket);
    }

    /**
     * Returns whether the client is currently connected
     * Also checks the state of the underlying socket.
     *
     * @return bool
     */
    public function isConnected()
    {
        if (false === $this->connected) {
            return false;
        }

        // Check if the socket is still connected
        if (false === $this->socket->isConnected()) {
            $this->connected = false;

            return false;
        }

        return true;
    }

    /**
     * Receives data sent by the server.
     *
     * @return array<Payload> Payload received since the last call to receive()
     */
    public function receive(): ?array
    {
        if (!$this->isConnected()) {
            return null;
        }

        $data = $this->socket->receive();

        if (!$data) {
            return [];
        }

        $this->payloadHandler->handle($data);
        $received = $this->received;
        $this->received = [];

        return $received;
    }

    /**
     * Connect to the server.
     *
     * @throws HandshakeException
     * @throws SocketException
     *
     * @return bool Whether a new connection was made
     */
    public function connect(): bool
    {
        if ($this->isConnected()) {
            return false;
        }

        try {
            $this->socket->connect();
        } catch (\Exception $ex) {
            return false;
        }

        $key = $this->protocol->generateKey();
        $handshake = $this->protocol->getRequestHandshake(
            $this->uri,
            $key,
            $this->origin,
            $this->headers
        );

        $this->socket->send($handshake);
        $response = $this->socket->receive(self::MAX_HANDSHAKE_RESPONSE);

        return $this->connected =
            $this->protocol->validateResponseHandshake($response, $key);
    }

    /**
     * Disconnects the underlying socket, and marks the client as disconnected.
     *
     * @param int $reason Reason for disconnecting. See Protocol::CLOSE_
     *
     * @throws SocketException
     * @throws FrameException
     */
    public function disconnect(int $reason = Protocol::CLOSE_NORMAL): bool
    {
        if (false === $this->connected) {
            return false;
        }

        $payload = $this->protocol->getClosePayload($reason);

        if ($this->socket) {
            if (!$payload->sendToSocket($this->socket)) {
                throw new FrameException('Unexpected exception when sending Close frame.');
            }
            // The client SHOULD wait for the server to close the connection
            $this->socket->receive();
            $this->socket->disconnect();
        }

        $this->connected = false;

        return true;
    }

    /**
     * Configure options.
     */
    protected function configure(array $options): void
    {
        $options = \array_merge([
            'socket_class' => ClientSocket::class,
            'on_data_callback' => null,
            'socket_options' => [],
        ], $options);

        parent::configure($options);
    }

    /**
     * Waits for data to become available on the socket.
     *
     * @param float $maxSeconds the maximum amount of time to wait for data, in seconds
     *
     * @return ?bool returns true if data is available, false if the wait timed out, and null on error
     */
    public function waitForData(float $maxSeconds): ?bool
    {
        $read = [$this->socket->getResource()];
        $write = null;
        $except = null;
        $seconds = (int) \floor($maxSeconds);
        $microseconds = (int) (($maxSeconds - $seconds) * 1e6);
        $result = \stream_select($read, $write, $except, $seconds, $microseconds);
        if (false === $result) {
            // An error occurred. stream_select() probably triggered an error internally.
            return null;
        } elseif (0 === $result) {
            // Timeout occurred, no data available
            return false;
        }

        // Data is available
        return true;
    }
}
