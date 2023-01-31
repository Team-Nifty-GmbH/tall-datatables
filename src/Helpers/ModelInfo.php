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

        $cache = Cache::supportsTags() ? Cache::tags(self::class) : Cache::store();

        return $cache
            ->rememberForever(
                config('tall-datatables.cache_key') . '.modelInfo:' . md5((string) $model),
                function () use ($model) {
                    $modelInfo = parent::forModel($model);
                    $modelInfo->attributes = $modelInfo
                        ->attributes
                        ->map(function (Attribute $attribute) use ($model) {
                            $attribute->formatter = $attribute->getFormatterType($model);

                            return $attribute;
                        });

                    return $modelInfo;
                }
            );
    }

    public static function forAllModels(
        string $directory = null,
        string $basePath = null,
        string $baseNamespace = null
    ): Collection {
        return ModelFinder::all($directory, $basePath, $baseNamespace)
            ->map(function (string $model) {
                return self::forModel($model);
            });
    }
}
