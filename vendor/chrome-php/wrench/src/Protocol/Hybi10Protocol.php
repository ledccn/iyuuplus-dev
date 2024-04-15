<?php

namespace Wrench\Protocol;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc6455
 */
class Hybi10Protocol extends HybiProtocol
{
    private const VERSION = 10;

    public function getVersion(): int
    {
        return self::VERSION;
    }

    public function acceptsVersion(int $version): bool
    {
        return self::VERSION === $version;
    }
}
