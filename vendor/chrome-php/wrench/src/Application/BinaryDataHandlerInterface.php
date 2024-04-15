<?php

namespace Wrench\Application;

use Wrench\Connection;

interface BinaryDataHandlerInterface
{
    /**
     * Handle binary data received from a client.
     */
    public function onBinaryData(string $binaryData, Connection $connection): void;
}
