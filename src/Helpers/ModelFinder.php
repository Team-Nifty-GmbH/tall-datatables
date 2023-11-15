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
        $paramHash = md5(serialize(func_get_args()));

        $cached = Cache::get(config('tall-datatables.cache_key') . '.modelFinder') ?? [];

        if ($cached[$paramHash] ?? false) {
            return $cached[$paramHash];
        }

        $cached[$paramHash] = parent::all($directory, $basePath, $baseNamespace);
        Cache::put(config('tall-datatables.cache_key') . '.modelFinder', $cached);

        return $cached[$paramHash];
    }
}
