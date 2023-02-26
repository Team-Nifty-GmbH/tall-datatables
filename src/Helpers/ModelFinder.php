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
        return Cache::rememberForever(
            config('tall-datatables.cache_key') . '.modelFinder',
            function () use ($directory, $basePath, $baseNamespace) {
                return parent::all($directory, $basePath, $baseNamespace);
            }
        );
    }
}
