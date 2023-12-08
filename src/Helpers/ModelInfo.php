<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Spatie\ModelInfo\Attributes\Attribute;
use Spatie\ModelInfo\ModelFinder;
use Spatie\ModelInfo\ModelInfo as BaseModelInfo;

class ModelInfo extends BaseModelInfo
{
    public static function forModel(string|Model|ReflectionClass $model): BaseModelInfo
    {
        if ($model instanceof ReflectionClass) {
            $model = $model->getName();
        }

        if (is_string($model)) {
            $model = new $model;
        }

        $cachedModelInfos = Cache::get(config('tall-datatables.cache_key') . '.modelInfo') ?? [];
        if (array_key_exists(get_class($model), $cachedModelInfos)) {
            return $cachedModelInfos[get_class($model)];
        }

        try {
            $modelInfo = parent::forModel($model);
        } catch (\Throwable $th) {
            $modelInfo = (new ReflectionClass(BaseModelInfo::class))->newInstanceWithoutConstructor();
            $modelInfo->relations = collect();

            return $modelInfo;
        }

        $modelInfo->attributes = $modelInfo
            ->attributes
            ->map(function (Attribute $attribute) use ($model) {
                $attribute = \TeamNiftyGmbH\DataTable\ModelInfo\Attribute::fromBase($attribute);
                $attribute->formatter = $attribute->getFormatterType($model);

                return $attribute;
            });

        $cachedModelInfos[get_class($model)] = $modelInfo;
        Cache::forever(config('tall-datatables.cache_key') . '.modelInfo', $cachedModelInfos);

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
