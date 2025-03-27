<?php

namespace TeamNiftyGmbH\DataTable\Exceptions;

use Exception;

final class LockedPublicPropertyTamperException extends Exception
{
    /**
     * @return static
     */
    public static function create(string $propertyName = ''): self
    {
        return new self('You are not allowed to tamper with the protected property ' . $propertyName);
    }
}
