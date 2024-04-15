<?php

namespace Wrench\Payload;

use Wrench\Frame\Frame;
use Wrench\Frame\HybiFrame;

class HybiPayload extends Payload
{
    protected function getFrame(): Frame
    {
        return new HybiFrame();
    }
}
