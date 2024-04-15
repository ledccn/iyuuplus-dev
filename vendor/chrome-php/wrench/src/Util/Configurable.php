<?php

namespace Wrench\Util;

use InvalidArgumentException;
use Wrench\Protocol\Protocol;
use Wrench\Protocol\Rfc6455Protocol;

/**
 * Configurable base class.
 */
abstract class Configurable
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var Protocol
     */
    protected $protocol;

    /**
     * @param array $options (optional)
     *                       Options:
     *                       - protocol             => Wrench\Protocol object, latest protocol
     *                       version used if not specified
     */
    public function __construct(array $options = [])
    {
        $this->configure($options);
        $this->configureProtocol();
    }

    /**
     * Configures the options.
     */
    protected function configure(array $options): void
    {
        $this->options = \array_merge([
            'protocol' => new Rfc6455Protocol(),
        ], $options);
    }

    /**
     * Configures the protocol option.
     *
     * @throws InvalidArgumentException
     */
    protected function configureProtocol(): void
    {
        $protocol = $this->options['protocol'];

        if (!$protocol || !($protocol instanceof Protocol)) {
            throw new InvalidArgumentException('Invalid protocol option');
        }

        $this->protocol = $protocol;
    }
}
