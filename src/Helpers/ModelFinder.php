<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\ModelInfo\ModelFinder as BaseModelFinder;

class ModelFinder extends BaseModelFinder
{
    public static function all(
        string $directory = null,
        string $basePath = null,
        string $baseNamespace = null,
    ): Collection {
        $cache = Cache::supportsTags() ? Cache::tags(self::class) : Cache::store();

        return $cache
            ->rememberForever(
                cache_key(self::class),
                function () use ($directory, $basePath, $baseNamespace) {
                    return parent::all($directory, $basePath, $baseNamespace);
                }
            );
    }
}
