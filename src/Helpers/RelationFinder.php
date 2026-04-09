<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation as IlluminateRelation;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use TeamNiftyGmbH\DataTable\DataTransferObjects\Relation;
use Throwable;

class RelationFinder
{
    /**
     * @param  class-string<Model>|Model  $model
     * @return Collection<Relation>
     */
    public static function forModel(string|Model $model): Collection
    {
        if (is_string($model)) {
            $model = app($model);
        }

        return (new static())->relations($model);
    }

    /**
     * @return Collection<Relation>
     */
    public function relations(Model $model): Collection
    {
        $class = new ReflectionClass($model);
        $relationResolvers = $class->getProperty('relationResolvers');

        $relations = [];
        foreach (data_get($relationResolvers->getValue($model), get_class($model), []) as $relationName => $closure) {
            try {
                $relation = $closure($model);
                $relations[] = new Relation(
                    $relationName,
                    get_class($relation),
                    $relation->getRelated()::class,
                );
            } catch (Throwable) {
                // Skip dynamic relations that fail to resolve
            }
        }

        return collect($class->getMethods())
            ->filter(fn (ReflectionMethod $method) => $this->hasRelationReturnType($method))
            ->map(function (ReflectionMethod $method) use ($model) {
                try {
                    $relation = $method->invoke($model);
                } catch (Throwable) {
                    return null;
                }

                return new Relation(
                    $method->getName(),
                    (string) $method->getReturnType(),
                    $relation->getRelated()::class,
                );
            })
            ->merge($relations)
            ->filter();
    }

    protected function hasRelationReturnType(ReflectionMethod $method): bool
    {
        if ($method->getReturnType() instanceof ReflectionNamedType) {
            $returnType = $method->getReturnType()->getName();

            return is_a($returnType, IlluminateRelation::class, true);
        }

        if ($method->getReturnType() instanceof ReflectionUnionType) {
            foreach ($method->getReturnType()->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType) {
                    $returnType = $type->getName();

                    if (is_a($returnType, IlluminateRelation::class, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
