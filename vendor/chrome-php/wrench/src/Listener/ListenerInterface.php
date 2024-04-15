<?php

namespace Wrench\Listener;

use Wrench\Server;

interface ListenerInterface
{
    public function listen(Server $server): void;
}
