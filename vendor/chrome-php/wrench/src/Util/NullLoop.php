<?php

namespace Wrench\Util;

class NullLoop implements LoopInterface
{
    public function shouldContinue(): bool
    {
        return true;
    }
}
