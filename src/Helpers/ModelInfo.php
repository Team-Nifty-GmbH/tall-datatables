<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Spatie\ModelInfo\Attributes\Attribute;
use Spatie\ModelInfo\ModelInfo as BaseModelInfo;

class ModelInfo extends BaseModelInfo
{
    /**
     * @param string|Model|ReflectionClass $model
     * @return BaseModelInfo
     */
    public static function forModel(string|Model|ReflectionClass $model): BaseModelInfo
    {
        if ($model instanceof ReflectionClass) {
            $model = $model->getName();
        }

        if (is_string($model)) {
            $model = new $model;
        }

        /** @var Collection|null $cachedModelInfoCollection * */
        $cachedModelInfoCollection = Cache::get(config('tall-datatables.cache_key') . '.modelInfo');

        if ($cachedModelInfo = $cachedModelInfoCollection
            ?->first(fn (BaseModelInfo $modelInfo) => $modelInfo->class === $model::class)) {
            return $cachedModelInfo;
        }

        $modelInfo = parent::forModel($model);
        $modelInfo->attributes = $modelInfo
            ->attributes
            ->map(function (Attribute $attribute) use ($model) {
                $attribute->formatter = $attribute->getFormatterType($model);

                return $attribute;
            });

        if ($cachedModelInfoCollection) {
            $cachedModelInfoCollection->push($modelInfo);
            Cache::forever(config('tall-datatables.cache_key') . '.modelInfo', $cachedModelInfoCollection);
        }

        return $modelInfo;
    }

    /**
     * @param string|null $directory
     * @param string|null $basePath
     * @param string|null $baseNamespace
     * @return Collection
     */
    public static function forAllModels(
        string $directory = null,
        string $basePath = null,
        string $baseNamespace = null
    ): Collection {
        return Cache::rememberForever(
            config('tall-datatables.cache_key') . '.modelInfo',
            function () use ($directory, $basePath, $baseNamespace) {
                return ModelFinder::all($directory, $basePath, $baseNamespace)
                    ->map(function (string $model) {
                        return self::forModel($model);
                    });
            }
        );
    }
}
