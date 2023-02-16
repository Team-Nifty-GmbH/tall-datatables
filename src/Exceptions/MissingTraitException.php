<?php

namespace TeamNiftyGmbH\DataTable\Exceptions;

final class MissingTraitException extends \Exception
{
    /**
     * @return static
     */
    public static function create(mixed $class, string $trait): self
    {
        return new self($class . ' must use the ' . $trait . ' trait');
    }
}
