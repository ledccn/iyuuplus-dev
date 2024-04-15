<?php

namespace Wrench;

interface ResourceInterface
{
    public function getResourceId(): ?int;

    /**
     * @return resource|null
     */
    public function getResource();
}
