<?php

namespace Wrench\Listener;

use Wrench\Connection;

interface HandshakeRequestListenerInterface
{
    public function onHandshakeRequest(
        Connection $connection,
        string $path,
        string $origin,
        string $key,
        array $extensions
    ): void;
}
