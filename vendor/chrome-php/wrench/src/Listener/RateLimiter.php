<?php

namespace Wrench\Listener;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Wrench\Connection;
use Wrench\Protocol\Protocol;
use Wrench\Server;
use Wrench\Util\Configurable;

class RateLimiter extends Configurable implements ListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * The server being limited.
     *
     * @var Server
     */
    protected $server;

    /**
     * Connection counts per IP address.
     *
     * @var array<int>
     */
    protected $ips = [];

    /**
     * Request tokens per IP address.
     *
     * @var array<array<int>>
     */
    protected $requests = [];

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->logger = new NullLogger();
    }

    public function listen(Server $server): void
    {
        $this->server = $server;

        $server->addListener(
            Server::EVENT_SOCKET_CONNECT,
            [$this, 'onSocketConnect']
        );

        $server->addListener(
            Server::EVENT_SOCKET_DISCONNECT,
            [$this, 'onSocketDisconnect']
        );

        $server->addListener(
            Server::EVENT_CLIENT_DATA,
            [$this, 'onClientData']
        );
    }

    /**
     * @param resource $socket
     */
    public function onSocketConnect($socket, Connection $connection): void
    {
        $this->checkConnections($connection);
        $this->checkConnectionsPerIp($connection);
    }

    /**
     * Idempotent.
     */
    protected function checkConnections(Connection $connection): void
    {
        $connections = $connection->getConnectionManager()->count();

        if ($connections > $this->options['connections']) {
            $this->limit($connection, 'Max connections');
        }
    }

    /**
     * Limits the given connection.
     */
    protected function limit(Connection $connection, string $reason): void
    {
        $this->logger->notice(
            \sprintf('Limiting connection %s: %s', $connection->getIp(), $reason)
        );

        $connection->close(Protocol::CLOSE_GOING_AWAY);
    }

    /**
     * NOT idempotent, call once per connection.
     */
    protected function checkConnectionsPerIp(Connection $connection): void
    {
        $ip = $connection->getIp();

        if (!$ip) {
            $this->logger->warning('Cannot check connections per IP');

            return;
        }

        if (!isset($this->ips[$ip])) {
            $this->ips[$ip] = 1;
        } else {
            $this->ips[$ip] = \min(
                $this->options['connections_per_ip'],
                $this->ips[$ip] + 1
            );
        }

        if ($this->ips[$ip] > $this->options['connections_per_ip']) {
            $this->limit($connection, 'Connections per IP');
        }
    }

    /**
     * @param resource $socket
     */
    public function onSocketDisconnect($socket, Connection $connection): void
    {
        $this->releaseConnection($connection);
    }

    /**
     * NOT idempotent, call once per disconnection.
     */
    protected function releaseConnection(Connection $connection): void
    {
        $ip = $connection->getIp();

        if (!$ip) {
            $this->logger->warning('Cannot release connection');

            return;
        }

        if (!isset($this->ips[$ip])) {
            $this->ips[$ip] = 0;
        } else {
            $this->ips[$ip] = \max(0, $this->ips[$ip] - 1);
        }

        unset($this->requests[$connection->getId()]);
    }

    /**
     * @param resource $socket
     */
    public function onClientData($socket, Connection $connection): void
    {
        $this->checkRequestsPerMinute($connection);
    }

    /**
     * NOT idempotent, call once per data.
     */
    protected function checkRequestsPerMinute(Connection $connection): void
    {
        $id = $connection->getId();

        if (!isset($this->requests[$id])) {
            $this->requests[$id] = [];
        }

        // Add current token
        $this->requests[$id][] = \time();

        // Expire old tokens
        while (\reset($this->requests[$id]) < \time() - 60) {
            \array_shift($this->requests[$id]);
        }

        if (\count($this->requests[$id]) > $this->options['requests_per_minute']) {
            $this->limit($connection, 'Requests per minute');
        }
    }

    protected function configure(array $options): void
    {
        $options = \array_merge([
            'connections' => 200, // Total
            'connections_per_ip' => 5,   // At once
            'requests_per_minute' => 200,  // Per connection
        ], $options);

        parent::configure($options);
    }
}
