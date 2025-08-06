<?php

namespace Wrench\Exception;

use Throwable;
use Wrench\Exception\Exception as WrenchException;
use Wrench\Protocol\Protocol;

class HandshakeException extends WrenchException
{
    public function __construct(string $message = '', ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $code ?? Protocol::HTTP_SERVER_ERROR, $previous);
    }
}
