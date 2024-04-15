<?php

namespace Wrench\Socket;

class ServerClientSocket extends AbstractSocket
{
    /**
     * A server client socket is accepted from a listening socket, so there's
     * no need to call ->connect() or whatnot.
     *
     * @param resource|null $accepted_socket
     */
    public function __construct($accepted_socket, array $options = [])
    {
        parent::__construct($options);

        $this->socket = $accepted_socket;
        $this->connected = null !== $accepted_socket;
    }
}
