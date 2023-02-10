<?php

namespace TeamNiftyGmbH\DataTable\Exceptions;

final class MissingTraitException extends \Exception
{
    /**
     * @param mixed $class
     * @param string $trait
     * @return static
     */
    public static function create(mixed $class, string $trait): self
    {
        return new self($class . ' must use the ' . $trait . ' trait');
    }
}
