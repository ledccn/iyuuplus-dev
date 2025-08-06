<?php

namespace Wrench\Frame;

use InvalidArgumentException;
use Wrench\Exception\FrameException;
use Wrench\Protocol\Protocol;

class HybiFrame extends Frame
{
    // First byte
    public const BITFIELD_FINAL = 0x80;
    public const BITFIELD_RSV1 = 0x40;
    public const BITFIELD_RSV2 = 0x20;
    public const BITFIELD_RSV3 = 0x10;
    public const BITFIELD_TYPE = 0x0F;

    // Second byte
    public const BITFIELD_MASKED = 0x80;
    public const BITFIELD_INITIAL_LENGTH = 0x7F;

    // The inital byte offset before
    public const BYTE_HEADER = 0;
    public const BYTE_MASKED = 1;
    public const BYTE_INITIAL_LENGTH = 1;

    /**
     * Whether the payload is masked.
     *
     * @var bool|null
     */
    protected $masked = null;

    /**
     * Masking key.
     *
     * @var string|null
     */
    protected $mask = null;

    /**
     * @var int|null
     */
    protected $offset_payload = null;

    /**
     * @var int|null
     */
    protected $offset_mask = null;

    /**
     * Encode a frame.
     *
     *     ws-frame         = frame-fin           ; 1 bit in length
     *                        frame-rsv1          ; 1 bit in length
     *                        frame-rsv2          ; 1 bit in length
     *                        frame-rsv3          ; 1 bit in length
     *                        frame-opcode        ; 4 bits in length
     *                        frame-masked        ; 1 bit in length
     *                        frame-payload-length   ; either 7, 7+16,
     *                                               ; or 7+64 bits in
     *                                               ; length
     *                        [ frame-masking-key ]  ; 32 bits in length
     *                        frame-payload-data     ; n*8 bits in
     *                                               ; length, where
     *                                               ; n >= 0
     *
     * @return static
     */
    public function encode(string $payload, int $type = Protocol::TYPE_TEXT, bool $masked = false): Frame
    {
        if (!\in_array($type, Protocol::FRAME_TYPES, true)) {
            throw new InvalidArgumentException('Invalid frame type');
        }

        $this->type = $type;
        $this->masked = $masked;
        $this->payload = $payload;
        $this->length = \strlen($this->payload);

        $this->buffer = "\x00\x00";

        // FIN + opcode byte
        $this->buffer[self::BYTE_HEADER] = \chr(
            (self::BITFIELD_TYPE & $this->type)
                | self::BITFIELD_FINAL
        );

        $masked_bit = $this->masked ? self::BITFIELD_MASKED : 0;

        if ($this->length <= 125) {
            $this->buffer[self::BYTE_INITIAL_LENGTH] = \chr(
                ($this->length & self::BITFIELD_INITIAL_LENGTH)
                    | $masked_bit
            );
        } elseif ($this->length <= 65535) {
            $this->buffer[self::BYTE_INITIAL_LENGTH] = \chr(
                (126 & self::BITFIELD_INITIAL_LENGTH)
                    | $masked_bit
            );
            $this->buffer .= \pack('n', $this->length);
        } else {
            $this->buffer[self::BYTE_INITIAL_LENGTH] = \chr(
                (127 & self::BITFIELD_INITIAL_LENGTH)
                    | $masked_bit
            );
            if (\PHP_INT_SIZE === 4) {
                // J is not available on 32-bit PHP
                $this->buffer .= \pack('NN', 0, $this->length);
            } else {
                $this->buffer .= \pack('J', $this->length);
            }
        }

        if ($this->masked) {
            $this->mask = $this->generateMask();
            $this->offset_mask = \strlen($this->buffer);
            $this->buffer .= $this->mask;
            $this->offset_payload = \strlen($this->buffer);
            $this->buffer .= $this->mask($this->payload);
        } else {
            $this->offset_payload = \strlen($this->buffer);
            $this->buffer .= $this->payload;
        }

        return $this;
    }

    /**
     * Generates a suitable masking key.
     *
     * @return string
     */
    protected function generateMask()
    {
        return \random_bytes(4);
    }

    /**
     * Masks/Unmasks the frame.
     */
    protected function mask(string $payload): string
    {
        $length = \strlen($payload);
        $mask = $this->getMask();

        $unmasked = '';
        for ($i = 0; $i < $length; ++$i) {
            $unmasked .= $payload[$i] ^ $mask[$i % 4];
        }

        return $unmasked;
    }

    /**
     * Gets the mask.
     *
     * @throws FrameException
     */
    protected function getMask(): string
    {
        if (!isset($this->mask)) {
            if (!$this->isMasked()) {
                throw new FrameException('Cannot get mask: frame is not masked');
            }
            $this->mask = \substr($this->buffer, $this->getMaskOffset(), $this->getMaskSize());
        }

        return $this->mask;
    }

    /**
     * Whether the frame is masked.
     */
    public function isMasked(): bool
    {
        if (!isset($this->masked)) {
            if (!isset($this->buffer[1])) {
                throw new FrameException('Cannot tell if frame is masked: not enough frame data received');
            }
            $this->masked = (bool) (\ord($this->buffer[1]) & self::BITFIELD_MASKED);
        }

        return $this->masked;
    }

    /**
     * Gets the offset in the frame to the masking bytes.
     */
    protected function getMaskOffset(): int
    {
        if (!isset($this->offset_mask)) {
            $offset = self::BYTE_INITIAL_LENGTH + 1;
            $offset += $this->getLengthSize();
            $this->offset_mask = $offset;
        }

        return $this->offset_mask;
    }

    /**
     * Returns the byte size of the length part of the frame
     * not including the initial 7 bit part.
     */
    protected function getLengthSize(): int
    {
        $initial = $this->getInitialLength();

        if (126 === $initial) {
            return 2;
        }

        if (127 === $initial) {
            return 8;
        }

        return 0;
    }

    /**
     * Gets the inital length value, stored in the first length byte.
     *
     * This determines how the rest of the length value is parsed out of the
     * frame.
     */
    protected function getInitialLength(): int
    {
        if (!isset($this->buffer[self::BYTE_INITIAL_LENGTH])) {
            throw new FrameException('Cannot yet tell expected length');
        }

        return (int) (\ord($this->buffer[self::BYTE_INITIAL_LENGTH]) & self::BITFIELD_INITIAL_LENGTH);
    }

    /**
     * Returns the byte size of the mask part of the frame.
     */
    protected function getMaskSize(): int
    {
        if ($this->isMasked()) {
            return 4;
        }

        return 0;
    }

    /**
     * Gets the offset of the payload in the frame.
     */
    protected function getPayloadOffset(): int
    {
        if (!isset($this->offset_payload)) {
            $offset = $this->getMaskOffset();
            $offset += $this->getMaskSize();
            $this->offset_payload = $offset;
        }

        return $this->offset_payload;
    }

    public function receiveData($data): void
    {
        if ($this->getBufferLength() <= self::BYTE_INITIAL_LENGTH) {
            $this->length = null;
            $this->offset_payload = null;
        }
        parent::receiveData($data);
    }

    public function isFinal(): bool
    {
        if (!isset($this->buffer[self::BYTE_HEADER])) {
            throw new FrameException('Cannot yet tell if frame is final');
        }

        return (bool) (\ord($this->buffer[self::BYTE_HEADER]) & self::BITFIELD_FINAL);
    }

    /**
     * @throws FrameException
     */
    public function getType(): int
    {
        if (!isset($this->buffer[self::BYTE_HEADER])) {
            throw new FrameException('Cannot yet tell type of frame');
        }

        $type = (int) (\ord($this->buffer[self::BYTE_HEADER]) & self::BITFIELD_TYPE);

        if (!\in_array($type, Protocol::FRAME_TYPES, true)) {
            throw new FrameException('Invalid payload type');
        }

        return $type;
    }

    protected function getExpectedBufferLength(): int
    {
        return $this->getLength() + $this->getPayloadOffset();
    }

    public function getLength(): int
    {
        if (!$this->length) {
            $initial = $this->getInitialLength();

            if ($initial < 126) {
                $this->length = $initial;
            } else {
                // Extended payload length: 2 or 8 bytes
                $start = self::BYTE_INITIAL_LENGTH + 1;
                $end = self::BYTE_INITIAL_LENGTH + $this->getLengthSize();

                if ($end >= $this->getBufferLength()) {
                    throw new FrameException('Cannot get extended length: need more data');
                }

                $length = 0;
                for ($i = $start; $i <= $end; ++$i) {
                    $length <<= 8;
                    $length += \ord($this->buffer[$i]);
                }

                $this->length = $length;
            }
        }

        return $this->length;
    }

    protected function decodeFramePayloadFromBuffer(): void
    {
        $payload = \substr($this->buffer, $this->getPayloadOffset());

        if ($this->isMasked()) {
            $payload = $this->mask($payload);
        }

        $this->payload = $payload;
    }
}
