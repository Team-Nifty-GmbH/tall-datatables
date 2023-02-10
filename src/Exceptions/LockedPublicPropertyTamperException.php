<?php

namespace TeamNiftyGmbH\DataTable\Exceptions;

final class LockedPublicPropertyTamperException extends \Exception
{
    /**
     * @param string $propertyName
     * @return static
     */
    public static function create(string $propertyName = ''): self
    {
        return new self('You are not allowed to tamper with the protected property ' . $propertyName);
    }
}
