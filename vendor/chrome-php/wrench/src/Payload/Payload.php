<?php

namespace Wrench\Payload;

use Wrench\Exception\FrameException;
use Wrench\Exception\PayloadException;
use Wrench\Exception\SocketException;
use Wrench\Frame\Frame;
use Wrench\Protocol\Protocol;
use Wrench\Socket\AbstractSocket;

/**
 * Represents a WebSocket protocol payload, which may be made up of multiple
 * frames.
 */
abstract class Payload
{
    /**
     * A payload may consist of one or more frames.
     *
     * @var Frame[]
     */
    protected $frames = [];

    /**
     * String representation of the payload contents.
     *
     * @var string Binary
     */
    protected $buffer;

    /**
     * Encodes a payload.
     *
     * @param string $data
     * @param int    $type
     * @param bool   $masked
     *
     * @return $this
     *
     * @todo No splitting into multiple frames just yet
     */
    public function encode(string $data, int $type = Protocol::TYPE_TEXT, bool $masked = false): self
    {
        $this->frames = [];

        $frame = $this->getFrame();
        $this->frames[] = $frame;

        $frame->encode($data, $type, $masked);

        return $this;
    }

    /**
     * Get a frame object.
     */
    abstract protected function getFrame(): Frame;

    /**
     * Whether this payload is waiting for more data.
     */
    public function isWaitingForData(): bool
    {
        return $this->getRemainingData() > 0;
    }

    /**
     * Gets the number of remaining bytes before this payload will be
     * complete.
     *
     * May return 0 (no more bytes required) or null (unknown number of bytes
     * required).
     */
    public function getRemainingData(): ?int
    {
        if ($this->isComplete()) {
            return 0;
        }

        try {
            if ($this->getCurrentFrame()->isFinal()) {
                return $this->getCurrentFrame()->getRemainingData();
            }
        } catch (FrameException $e) {
            return null;
        }

        return null;
    }

    /**
     * Whether the payload is complete.
     */
    public function isComplete(): bool
    {
        return $this->getCurrentFrame()->isComplete() && $this->getCurrentFrame()->isFinal();
    }

    /**
     * Gets the current frame for the payload.
     *
     * @return mixed
     */
    protected function getCurrentFrame()
    {
        if (empty($this->frames)) {
            $this->frames[] = $this->getFrame();
        }

        return \end($this->frames);
    }

    /**
     * @throws FrameException
     * @throws SocketException
     */
    public function sendToSocket(AbstractSocket $socket): bool
    {
        $success = true;

        foreach ($this->frames as $frame) {
            $success = $success && (
                null !== $socket->send($frame->getFrameBuffer())
            );
        }

        return $success;
    }

    /**
     * Receive raw data into the payload.
     *
     * @throws PayloadException
     */
    public function receiveData(string $data): void
    {
        $chunkSize = null;

        while ($data) {
            $frame = $this->getReceivingFrame();

            $remaining = $frame->getRemainingData();

            if (null === $remaining) {
                $chunkSize = 2;
            } elseif ($remaining > 0) {
                $chunkSize = $remaining;
            }

            $chunkSize = \min(\strlen($data), $chunkSize);
            $chunk = \substr($data, 0, $chunkSize);
            $data = \substr($data, $chunkSize);

            $frame->receiveData($chunk);
        }
    }

    /**
     * Gets the frame into which data should be receieved.
     *
     * @throws PayloadException
     */
    protected function getReceivingFrame(): Frame
    {
        $current = $this->getCurrentFrame();

        if ($current->isComplete()) {
            if ($current->isFinal()) {
                throw new PayloadException('Payload cannot receive data: it is already complete');
            } else {
                $this->frames[] = $current = $this->getFrame();
            }
        }

        return $current;
    }

    public function __toString(): string
    {
        try {
            return $this->getPayload();
        } catch (\Exception $e) {
            // __toString must not throw an exception
            return '';
        }
    }

    /**
     * @throws FrameException
     */
    public function getPayload(): string
    {
        $this->buffer = '';

        foreach ($this->frames as $frame) {
            $this->buffer .= $frame->getFramePayload();
        }

        return $this->buffer;
    }

    /**
     * Gets the type of the payload.
     *
     * The type of a payload is taken from its first frame.
     *
     * @throws PayloadException
     */
    public function getType(): int
    {
        if (!isset($this->frames[0])) {
            throw new PayloadException('Cannot tell payload type yet');
        }

        return $this->frames[0]->getType();
    }
}
