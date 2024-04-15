<?php

namespace Wrench\Application;

use Wrench\Connection;

interface DataHandlerInterface
{
    /**
     * Handle data received from a client.
     */
    public function onData(string $data, Connection $connection): void;
}
