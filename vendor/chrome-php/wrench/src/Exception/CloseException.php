<?php

namespace Wrench\Exception;

use Throwable;
use Wrench\Exception\Exception as WrenchException;
use Wrench\Protocol\Protocol;

/**
 * Close connection exception.
 */
class CloseException extends WrenchException
{
    public function __construct(string $message = '', ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $code ?? Protocol::CLOSE_UNEXPECTED, $previous);
    }
}
