<?php

namespace Wrench\Payload;

use InvalidArgumentException;
use Wrench\Exception\PayloadException;
use Wrench\Util\Configurable;

/**
 * Handles chunking and splitting of payloads into frames.
 */
class PayloadHandler extends Configurable
{
    /**
     * A callback that will be called when a complete payload is available.
     *
     * @var callable
     */
    protected $callback;

    /**
     * The current payload.
     */
    protected $payload;

    /**
     * @param callable $callback
     * @param array    $options
     *
     * @throws InvalidArgumentException
     */
    public function __construct(callable $callback, array $options = [])
    {
        parent::__construct($options);

        $this->callback = $callback;
    }

    /**
     * Handles the raw socket data given.
     *
     * @param string $data
     *
     * @throws PayloadException
     */
    public function handle(string $data): void
    {
        if (!$this->payload) {
            $this->payload = $this->protocol->getPayload();
        }

        while ($data) { // Each iteration pulls off a single payload chunk
            $size = \strlen($data);
            $remaining = $this->payload->getRemainingData();

            // If we don't yet know how much data is remaining, read data into
            // the payload in two byte chunks (the size of a WebSocket frame
            // header to get the initial length)
            //
            // Then re-loop. For extended lengths, this will happen once or four
            // times extra, as the extended length is read in.
            if (null === $remaining) {
                $chunkSize = 2;
            } elseif ($remaining > 0) {
                $chunkSize = $remaining;
            } else {
                $chunkSize = 0;
            }

            $chunkSize = \min(\strlen($data), $chunkSize);
            $chunk = \substr($data, 0, $chunkSize);
            $data = \substr($data, $chunkSize);

            $this->payload->receiveData($chunk);

            if (0 !== $remaining && !$this->payload->isComplete()) {
                continue;
            }

            if ($this->payload->isComplete()) {
                $this->emit($this->payload);
                $this->payload = $this->protocol->getPayload();
            } else {
                throw new PayloadException('Payload will not complete');
            }
        }
    }

    /**
     * Emits a complete payload to the callback.
     *
     * @param Payload $payload
     */
    protected function emit(Payload $payload): void
    {
        \call_user_func($this->callback, $payload);
    }
}
