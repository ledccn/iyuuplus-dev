<?php

namespace Wrench;

use Wrench\Listener\OriginPolicy;
use Wrench\Listener\RateLimiter;

class BasicServer extends Server
{
    protected $rateLimiter;
    protected $originPolicy;

    public function __construct(string $uri, array $options = [])
    {
        parent::__construct($uri, $options);

        $this->configureRateLimiter();
        $this->configureOriginPolicy();
    }

    protected function configureRateLimiter(): void
    {
        $class = $this->options['rate_limiter_class'];
        $this->rateLimiter = new $class($this->options['rate_limiter_options']);
        $this->rateLimiter->listen($this);
    }

    /**
     * Configures the origin policy.
     */
    protected function configureOriginPolicy(): void
    {
        $class = $this->options['origin_policy_class'];
        $this->originPolicy = new $class($this->options['allowed_origins']);

        if ($this->options['check_origin']) {
            $this->originPolicy->listen($this);
        }
    }

    /**
     * @see Wrench.Server::configure()
     */
    protected function configure(array $options): void
    {
        $options = \array_merge([
            'check_origin' => true,
            'allowed_origins' => [],
            'origin_policy_class' => OriginPolicy::class,
            'rate_limiter_class' => RateLimiter::class,
            'rate_limiter_options' => [
                'connections' => 200, // Total
                'connections_per_ip' => 5,   // At once
                'requests_per_minute' => 200,  // Per connection
            ],
        ], $options);

        parent::configure($options);
    }
}
