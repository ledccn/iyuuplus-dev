<?php

namespace Wrench\Application;

interface UpdateHandlerInterface
{
    /**
     * Handle an update tick.
     */
    public function onUpdate(): void;
}
