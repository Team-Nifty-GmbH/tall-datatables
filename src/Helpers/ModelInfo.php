<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Spatie\ModelInfo\Attributes\Attribute;
use Spatie\ModelInfo\Attributes\AttributeFinder;
use Spatie\ModelInfo\ModelInfo as BaseModelInfo;
use Spatie\ModelInfo\Relations\RelationFinder;

class ModelInfo extends BaseModelInfo
{
    private static ?array $cachedModelInfos = null;

    public static function forModel(string|Model|ReflectionClass $model): BaseModelInfo
    {
        if (is_null(static::$cachedModelInfos)) {
            static::$cachedModelInfos = Cache::get(config('tall-datatables.cache_key') . '.modelInfo')
                ?? [];
        }

        if ($model instanceof ReflectionClass) {
            $model = $model->getName();
        }

        $cacheKey = is_string($model) ? $model : get_class($model);
        if (array_key_exists($cacheKey, static::$cachedModelInfos)) {
            return static::$cachedModelInfos[$cacheKey];
        }

        if (is_string($model)) {
            $model = new $model;
        }

        static::registerTypeMappings($model->getConnection()->getDoctrineSchemaManager()->getDatabasePlatform());

        try {
            $relations = RelationFinder::forModel($model);
        } catch (\Throwable) {
            $relations = collect();
        }

        try {
            $attributes = AttributeFinder::forModel($model);
        } catch (\Throwable) {
            $attributes = collect();
        }

        $modelInfo = new self(
            $model::class,
            (new ReflectionClass($model))->getFileName(),
            $model->getConnection()->getName(),
            $model->getConnection()->getTablePrefix() . $model->getTable(),
            $relations,
            $attributes,
            self::getTraits($model),
            self::getExtraModelInfo($model),
        );

        $modelInfo->attributes = $modelInfo
            ->attributes
            ->map(function (Attribute $attribute) use ($model) {
                $attribute = \TeamNiftyGmbH\DataTable\ModelInfo\Attribute::fromBase($attribute);
                $attribute->formatter = $attribute->getFormatterType($model);

                return $attribute;
            });

        static::$cachedModelInfos[get_class($model)] = $modelInfo;
        Cache::forever(config('tall-datatables.cache_key') . '.modelInfo', static::$cachedModelInfos);

        return $modelInfo;
    }

    /**
     * @return Collection<\Spatie\ModelInfo\ModelInfo>
     */
    public static function forAllModels(
        ?string $directory = null,
        ?string $basePath = null,
        ?string $baseNamespace = null
    ): Collection {
        return ModelFinder::all($directory, $basePath, $baseNamespace)
            ->map(function (string $model) {
                return self::forModel($model);
            });
    }
}
