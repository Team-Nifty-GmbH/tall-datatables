<?php

namespace TeamNiftyGmbH\DataTable\Exceptions;

class LockedPublicPropertyTamperException extends \Exception
{
    /**
     * @param string $propertyName
     * @return static
     */
    public static function create(string $propertyName = ''): static
    {
        return new static('You are not allowed to tamper with the protected property ' . $propertyName);
    }
}
