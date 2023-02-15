<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Support\Str;
use ReflectionException;
use TeamNiftyGmbH\DataTable\Exceptions\LockedPublicPropertyTamperException;

trait WithLockedPublicPropertiesTrait
{
    /**
     * @throws LockedPublicPropertyTamperException
     * @throws ReflectionException
     */
    public function updatingWithLockedPublicPropertiesTrait(string $name): void
    {
        $propertyName = Str::of($name)->explode('.')->first();
        $reflectionProperty = new \ReflectionProperty($this, $propertyName);

        if (Str::of($reflectionProperty->getDocComment())->contains('@locked')) {
            throw LockedPublicPropertyTamperException::create($propertyName);
        }
    }
}
