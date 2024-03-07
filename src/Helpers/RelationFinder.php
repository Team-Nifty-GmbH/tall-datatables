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
            $model = new $model;
        }

        return (new static())->relations($model);
    }

    /**
     * @return Collection<Relation>
     */
    public function relations(Model $model): Collection
    {
        $class = new ReflectionClass($model);

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
            ->filter();
    }
}
