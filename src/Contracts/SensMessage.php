<?php

namespace Daworks\Sens\Contracts;

interface SensMessage
{
    /**
     * Serialize to Array.
     *
     * @return array
     */
    public function toArray();
}
