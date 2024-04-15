<?php

namespace Wrench;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;
use Wrench\Application\BinaryDataHandlerInterface;
use Wrench\Application\ConnectionHandlerInterface;
use Wrench\Application\DataHandlerInterface;
use Wrench\Application\UpdateHandlerInterface;
use Wrench\Exception\BadRequestException;
use Wrench\Exception\CloseException;
use Wrench\Exception\ConnectionException;
use Wrench\Exception\Exception as WrenchException;
use Wrench\Exception\HandshakeException;
use Wrench\Payload\Payload;
use Wrench\Payload\PayloadHandler;
use Wrench\Protocol\Protocol;
use Wrench\Socket\ServerClientSocket;
use Wrench\Util\Configurable;

/**
 * Represents a client connection on the server side.
 *
 * i.e. the `Server` manages a bunch of `Connection`s
 */
class Connection extends Configurable implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ConnectionManager
     */
    protected $manager;

    /**
     * Wraps the client connection resource.
     *
     * @var ServerClientSocket
     */
    protected $socket;

    /**
     * Whether the connection has successfully handshaken.
     *
     * @var bool
     */
    protected $handshaked = false;

    /**
     * The application this connection belongs to.
     *
     * @var DataHandlerInterface|ConnectionHandlerInterface|UpdateHandlerInterface|null
     */
    protected $application = null;

    /**
     * The IP address of the client.
     *
     * @var string
     */
    protected $ip;

    /**
     * The port of the client.
     *
     * @var int
     */
    protected $port;

    /**
     * The array of headers included with the original request (like Cookie for example)
     * The headers specific to the web sockets handshaking have been stripped out.
     *
     * @var array
     */
    protected $headers = null;

    /**
     * The array of query parameters included in the original request
     * The array is in the format 'key' => 'value'.
     *
     * @var array
     */
    protected $queryParams = null;

    /**
     * Connection ID.
     *
     * @var string|null
     */
    protected $id = null;

    /**
     * @var PayloadHandler
     */
    protected $payloadHandler;

    public function __construct(
        ConnectionManager $manager,
        ServerClientSocket $socket,
        array $options = []
    ) {
        $this->manager = $manager;
        $this->socket = $socket;
        $this->logger = new NullLogger();

        parent::__construct($options);

        $this->configureClientInformation();
        $this->configurePayloadHandler();
    }

    /**
     * @throws RuntimeException
     */
    protected function configureClientInformation(): void
    {
        $this->ip = $this->socket->getIp();
        $this->port = $this->socket->getPort();
        $this->generateClientId();
    }

    /**
     * Configures the client ID.
     *
     * We hash the client ID to prevent leakage of information if another client
     * happens to get a hold of an ID. The secret *must* be lengthy, and must
     * be kept secret for this to work: otherwise it's trivial to search the space
     * of possible IP addresses/ports (well, if not trivial, at least very fast).
     */
    protected function generateClientId(): void
    {
        $this->id = \bin2hex(\random_bytes(32));
    }

    protected function configurePayloadHandler(): void
    {
        $this->payloadHandler = new PayloadHandler(
            [$this, 'handlePayload'],
            $this->options
        );
    }

    /**
     * Gets the connection manager of this connection.
     *
     * @return \Wrench\ConnectionManager
     */
    public function getConnectionManager(): ConnectionManager
    {
        return $this->manager;
    }

    /**
     * Handle a complete payload received from the client.
     *
     * Public because called from our PayloadHandler
     *
     * @param Payload $payload
     *
     * @throws ConnectionException
     */
    public function handlePayload(Payload $payload): void
    {
        $app = $this->getClientApplication();

        $this->logger->debug('Handling payload: '.$payload->getPayload());

        switch ($type = $payload->getType()) {
            case Protocol::TYPE_TEXT:
                if ($app instanceof DataHandlerInterface) {
                    $app->onData((string) $payload, $this);
                }

                return;

            case Protocol::TYPE_BINARY:
                if ($app instanceof BinaryDataHandlerInterface) {
                    $app->onBinaryData((string) $payload, $this);
                } else {
                    $this->close(1003);
                }
                break;

            case Protocol::TYPE_PING:
                $this->logger->notice('Ping received');
                $this->send($payload->getPayload(), Protocol::TYPE_PONG);
                $this->logger->debug('Pong!');
                break;

            /**
             * A Pong frame MAY be sent unsolicited.  This serves as a
             * unidirectional heartbeat.  A response to an unsolicited Pong
             * frame is not expected.
             */
            case Protocol::TYPE_PONG:
                $this->logger->info('Received unsolicited pong');
                break;

            case Protocol::TYPE_CLOSE:
                $this->logger->notice('Close frame received');
                $this->close();
                $this->logger->debug('Disconnected');
                break;

            default:
                throw new ConnectionException('Unhandled payload type');
        }
    }

    /**
     * Gets the client application.
     *
     * @return BinaryDataHandlerInterface|ConnectionHandlerInterface|DataHandlerInterface|UpdateHandlerInterface|false
     */
    public function getClientApplication()
    {
        return $this->application ?? false;
    }

    /**
     * Closes the connection according to the WebSocket protocol.
     *
     * If an endpoint receives a Close frame and that endpoint did not
     * previously send a Close frame, the endpoint MUST send a Close frame
     * in response.  It SHOULD do so as soon as is practical.  An endpoint
     * MAY delay sending a close frame until its current message is sent
     * (for instance, if the majority of a fragmented message is already
     * sent, an endpoint MAY send the remaining fragments before sending a
     * Close frame).  However, there is no guarantee that the endpoint which
     * has already sent a Close frame will continue to process data.
     * After both sending and receiving a close message, an endpoint
     * considers the WebSocket connection closed, and MUST close the
     * underlying TCP connection.  The server MUST close the underlying TCP
     * connection immediately; the client SHOULD wait for the server to
     * close the connection but MAY close the connection at any time after
     * sending and receiving a close message, e.g. if it has not received a
     * TCP close from the server in a reasonable time period.
     *
     * @param int    $code
     * @param string $reason The human readable reason the connection was closed
     *
     * @return bool
     */
    public function close(int $code = Protocol::CLOSE_NORMAL, string $reason = null): bool
    {
        try {
            if (!$this->handshaked) {
                $response = $this->protocol->getResponseError($code);
                $this->socket->send($response);
            } else {
                $response = $this->protocol->getClosePayload($code, false);
                $response->sendToSocket($this->socket);
            }
        } catch (Throwable $e) {
            $this->logger->warning('Unable to send close message');
        }

        if ($this->application instanceof ConnectionHandlerInterface) {
            $this->application->onDisconnect($this);
        }

        $this->socket->disconnect();
        $this->manager->removeConnection($this);

        return true;
    }

    /**
     * Sends the payload to the connection.
     *
     * @param mixed $data
     * @param int   $type
     *
     * @return bool
     */
    public function send($data, int $type = Protocol::TYPE_TEXT): bool
    {
        if (!$this->handshaked) {
            throw new HandshakeException('Connection is not handshaked');
        }

        $payload = $this->protocol->getPayload();
        if (!\is_scalar($data) && !$data instanceof Payload) {
            $data = \json_encode($data);
        }

        // Servers don't send masked payloads
        $payload->encode($data, $type, false);

        if (!$payload->sendToSocket($this->socket)) {
            $this->logger->warning('Could not send payload to client');
            throw new ConnectionException('Could not send data to connection: '.$this->socket->getLastError());
        }

        return true;
    }

    /**
     * Processes data on the socket.
     *
     * @throws CloseException
     */
    public function process(): void
    {
        $data = $this->socket->receive();

        if ('' === $data) {
            throw new CloseException('Error reading data from socket: '.$this->socket->getLastError());
        }

        $this->onData($data);
    }

    /**
     * Data receiver.
     *
     * Called by the connection manager when the connection has received data
     *
     * @param string $data
     */
    public function onData($data): void
    {
        if ($this->handshaked) {
            $this->handle($data);
        } else {
            $this->handshake($data);
        }
    }

    /**
     * Performs a websocket handshake.
     *
     * @throws BadRequestException
     * @throws HandshakeException
     * @throws WrenchException
     */
    public function handshake(string $data): void
    {
        try {
            [$path, $origin, $key, $extensions, $protocol, $headers, $params]
                = $this->protocol->validateRequestHandshake($data);

            $this->headers = $headers;
            $this->queryParams = $params;

            $this->application = $this->manager->getApplicationForPath($path);
            if (!$this->application) {
                throw new BadRequestException('Invalid application');
            }

            $this->manager->getServer()->notify(
                Server::EVENT_HANDSHAKE_REQUEST,
                [$this, $path, $origin, $key, $extensions]
            );

            $response = $this->protocol->getResponseHandshake($key);

            if (!$this->socket->isConnected()) {
                throw new HandshakeException('Socket is not connected');
            }

            if (null === $this->socket->send($response)) {
                throw new HandshakeException('Could not send handshake response');
            }

            $this->handshaked = true;

            $this->logger->info(\sprintf(
                'Handshake successful: %s:%d (%s) connected to %s',
                $this->getIp(),
                $this->getPort(),
                $this->getId(),
                $path
            ));

            $this->manager->getServer()->notify(
                Server::EVENT_HANDSHAKE_SUCCESSFUL,
                [$this]
            );

            if ($this->application instanceof ConnectionHandlerInterface) {
                $this->application->onConnect($this);
            }
        } catch (WrenchException $e) {
            $this->logger->error('Handshake failed: {exception}', [
                'exception' => $e,
            ]);
            $this->close(Protocol::CLOSE_PROTOCOL_ERROR, (string) $e);
            throw $e;
        }
    }

    /**
     * Gets the IP address of the connection.
     *
     * @return string Usually dotted quad notation
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Gets the port of the connection.
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Gets the connection ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Handle data received from the client.
     *
     * The data passed in may belong to several different frames across one or
     * more protocols. It may not even contain a single complete frame. This method
     * manages slotting the data into separate payload objects.
     *
     * @todo An endpoint MUST be capable of handling control frames in the
     *        middle of a fragmented message.
     *
     * @param string $data
     *
     * @return void
     */
    public function handle($data): void
    {
        $this->payloadHandler->handle($data);
    }

    /**
     * Gets the non-web-sockets headers included with the original request.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Gets the query parameters included with the original request.
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Gets the socket object.
     *
     * @return Socket\ServerClientSocket
     */
    public function getSocket(): ServerClientSocket
    {
        return $this->socket;
    }

    /**
     * @see \Wrench\Util.Configurable::configure()
     */
    protected function configure(array $options): void
    {
        $options = \array_merge([
            'connection_id_secret' => 'asu5gj656h64Da(0crt8pud%^WAYWW$u76dwb',
            'connection_id_algo' => 'sha512',
        ], $options);

        parent::configure($options);
    }
}
