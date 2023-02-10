<?php

namespace TeamNiftyGmbH\DataTable\Tratis;

use Illuminate\Support\Str;
use ReflectionException;
use TeamNiftyGmbH\DataTable\Exceptions\LockedPublicPropertyTamperException;

trait WithLockedPublicPropertiesTrait
{
    /**
     * @throws LockedPublicPropertyTamperException|ReflectionException
     */
    public function updatingWithLockedPublicPropertiesTrait($name): void
    {
        $propertyName = Str::of($name)->explode('.')->first();
        $reflectionProperty = new \ReflectionProperty($this, $propertyName);

        if (Str::of($reflectionProperty->getDocComment())->contains('@locked')) {
            throw new LockedPublicPropertyTamperException($propertyName);
        }
    }
}