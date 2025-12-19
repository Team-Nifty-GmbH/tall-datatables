<?php

namespace TeamNiftyGmbH\DataTable\ModelInfo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation as IlluminateRelation;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Throwable;

class RelationFinder
{
    /**
     * @param  class-string<Model>|Model  $model
     * @return Collection<int, Relation>
     */
    public static function forModel(string|Model $model): Collection
    {
        if (is_string($model)) {
            $model = app($model);
        }

        return (new static)->relations($model);
    }

    /**
     * @return Collection<int, Relation>
     */
    public function relations(Model $model): Collection
    {
        $class = new ReflectionClass($model);
        $relationResolvers = $class->getProperty('relationResolvers');
        $relationResolvers->setAccessible(true);

        $relations = [];
        foreach (data_get($relationResolvers->getValue($model), get_class($model), []) as $relationName => $closure) {
            $relation = $closure($model);
            $relations[] = new Relation(
                $relationName,
                get_class($relation),
                $relation->getRelated()::class,
            );
        }

        return collect($class->getMethods())
            ->filter(fn (ReflectionMethod $method) => $this->hasRelationReturnType($method))
            ->map(function (ReflectionMethod $method) use ($model) {
                /** @var IlluminateRelation $relation */
                try {
                    $relation = $method->invoke($model);
                } catch (Throwable) {
                    return null;
                }

                return new Relation(
                    $method->getName(),
                    $method->getReturnType(),
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
                $returnType = $type->getName();

                if (is_a($returnType, IlluminateRelation::class, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
