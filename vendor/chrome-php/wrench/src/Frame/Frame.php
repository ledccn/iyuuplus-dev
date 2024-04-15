<?php

namespace Wrench\Frame;

use Wrench\Exception\FrameException;
use Wrench\Protocol\Protocol;

/**
 * Represents a WebSocket frame.
 */
abstract class Frame
{
    /**
     * The frame data length.
     *
     * @var int|null
     */
    protected $length = null;

    /**
     * The type of this payload.
     *
     * @var int|null
     */
    protected $type = null;

    /**
     * The buffer.
     *
     * May not be a complete payload, because this frame may still be receiving
     * data. See.
     *
     * @var string
     */
    protected $buffer = '';

    /**
     * The enclosed frame payload.
     *
     * May not be a complete payload, because this frame might indicate a continuation
     * frame. See isFinal() versus isComplete().
     *
     * @var string
     */
    protected $payload = '';

    /**
     * Gets the length of the payload.
     *
     * @throws FrameException
     */
    abstract public function getLength(): int;

    /**
     * Resets the frame and encodes the given data into it.
     *
     * @return static
     */
    abstract public function encode(string $payload, int $type = Protocol::TYPE_TEXT, bool $masked = false): self;

    /**
     * Whether the frame is the final one in a continuation.
     */
    abstract public function isFinal(): bool;

    abstract public function getType(): int;

    /**
     * Receieves data into the frame.
     */
    public function receiveData(string $data): void
    {
        $this->buffer .= $data;
    }

    /**
     * Whether this frame is waiting for more data.
     */
    public function isWaitingForData(): bool
    {
        return $this->getRemainingData() > 0;
    }

    /**
     * Gets the remaining number of bytes before this frame will be complete.
     */
    public function getRemainingData(): ?int
    {
        try {
            return $this->getExpectedBufferLength() - $this->getBufferLength();
        } catch (FrameException $e) {
            return null;
        }
    }

    /**
     * Gets the expected length of the buffer once all the data has been
     * receieved.
     *
     * @return int
     */
    abstract protected function getExpectedBufferLength(): int;

    /**
     * Gets the expected length of the frame payload.
     *
     * @return int
     */
    protected function getBufferLength(): int
    {
        return \strlen($this->buffer);
    }

    /**
     * Gets the contents of the frame payload.
     *
     * The frame must be complete to call this method.
     *
     * @throws FrameException
     */
    public function getFramePayload(): string
    {
        if (!$this->isComplete()) {
            throw new FrameException('Cannot get payload: frame is not complete');
        }

        if (!$this->payload && $this->buffer) {
            $this->decodeFramePayloadFromBuffer();
        }

        return $this->payload;
    }

    /**
     * Whether the frame is complete.
     */
    public function isComplete(): bool
    {
        if (!$this->buffer) {
            return false;
        }

        try {
            return $this->getBufferLength() >= $this->getExpectedBufferLength();
        } catch (FrameException $e) {
            return false;
        }
    }

    /**
     * Decodes a frame payload from the buffer.
     *
     * @return void
     */
    abstract protected function decodeFramePayloadFromBuffer(): void;

    /**
     * Gets the binary contents of the frame buffer.
     *
     * This is the encoded value, receieved into the frame with receiveData().
     *
     * @throws FrameException
     */
    public function getFrameBuffer(): string
    {
        if (!$this->buffer && $this->payload) {
            throw new FrameException('Cannot get frame buffer');
        }

        return $this->buffer;
    }
}
