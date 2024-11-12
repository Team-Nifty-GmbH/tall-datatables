<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Spatie\ModelInfo\Relations\Relation;
use Spatie\ModelInfo\Relations\RelationFinder as BaseRelationFinder;

class RelationFinder extends BaseRelationFinder
{
    public static function forModel(string|Model $model): Collection
    {
        if (is_string($model)) {
            $model = app($model);
        }

        return (new static)->relations($model);
    }

    /**
     * @return Collection<Relation>
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
                /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo $relation */
                try {
                    $relation = $method->invoke($model);
                } catch (\Throwable) {
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
}
