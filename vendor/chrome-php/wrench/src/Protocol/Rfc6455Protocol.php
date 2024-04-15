<?php

namespace Wrench\Protocol;

/**
 * This is the version of websockets used by Chrome versions 17 through 19.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6455
 */
class Rfc6455Protocol extends HybiProtocol
{
    private const VERSION = 13;

    public function getVersion(): int
    {
        return self::VERSION;
    }

    public function acceptsVersion(int $version): bool
    {
        return $version <= self::VERSION;
    }
}
