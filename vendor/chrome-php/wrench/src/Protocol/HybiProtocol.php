<?php

namespace Wrench\Protocol;

use Wrench\Payload\HybiPayload;
use Wrench\Payload\Payload;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc6455#section-5.2
 */
abstract class HybiProtocol extends Protocol
{
    /**
     * @return HybiPayload
     */
    public function getPayload(): Payload
    {
        return new HybiPayload();
    }
}
