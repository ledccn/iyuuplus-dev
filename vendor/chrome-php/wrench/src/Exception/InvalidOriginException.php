<?php

namespace Wrench\Exception;

use Throwable;
use Wrench\Protocol\Protocol;

/**
 * Invalid origin exception.
 */
class InvalidOriginException extends HandshakeException
{
    public function __construct(string $message = '', int $code = null, Throwable $previous = null)
    {
        parent::__construct($message, $code ?? Protocol::HTTP_FORBIDDEN, $previous);
    }
}
