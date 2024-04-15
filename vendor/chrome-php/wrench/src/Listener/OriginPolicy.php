<?php

namespace Wrench\Listener;

use Wrench\Connection;
use Wrench\Protocol\Protocol;
use Wrench\Server;

class OriginPolicy implements ListenerInterface, HandshakeRequestListenerInterface
{
    protected $allowed = [];

    public function __construct(array $allowed)
    {
        $this->allowed = $allowed;
    }

    /**
     * Closes the connection on handshake from an origin that isn't allowed.
     */
    public function onHandshakeRequest(
        Connection $connection,
        string $path,
        string $origin,
        string $key,
        array $extensions
    ): void {
        if (!$this->isAllowed($origin)) {
            $connection->close(Protocol::CLOSE_NORMAL, 'Not allowed origin during handshake request');
        }
    }

    /**
     * Whether the specified origin is allowed under this policy.
     */
    public function isAllowed(string $origin): bool
    {
        $scheme = \parse_url($origin, \PHP_URL_SCHEME);
        $host = \parse_url($origin, \PHP_URL_HOST) ?: $origin;

        foreach ($this->allowed as $allowed) {
            $allowed_scheme = \parse_url($allowed, \PHP_URL_SCHEME);

            if ($allowed_scheme && $scheme != $allowed_scheme) {
                continue;
            }

            $allowed_host = \parse_url($allowed, \PHP_URL_HOST) ?: $allowed;

            if ($host != $allowed_host) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function listen(Server $server): void
    {
        $server->addListener(
            Server::EVENT_HANDSHAKE_REQUEST,
            [$this, 'onHandshakeRequest']
        );
    }
}
