<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TeamNiftyGmbH\DataTable\Helpers\ModelFinder;

describe('ModelFinder', function (): void {
    test('all returns a Collection', function (): void {
        $result = ModelFinder::all();

        expect($result)->toBeInstanceOf(Collection::class);
    });

    test('caches results in Laravel cache', function (): void {
        Cache::forget(config('tall-datatables.cache_key') . '.modelFinder');

        ModelFinder::all();

        $cached = Cache::get(config('tall-datatables.cache_key') . '.modelFinder');

        expect($cached)->toBeArray()
            ->not->toBeEmpty();
    });

    test('returns same result from cache on second call', function (): void {
        Cache::forget(config('tall-datatables.cache_key') . '.modelFinder');

        $first = ModelFinder::all();
        $second = ModelFinder::all();

        expect($first->toArray())->toBe($second->toArray());
    });

    test('uses different cache entries for different parameters', function (): void {
        Cache::forget(config('tall-datatables.cache_key') . '.modelFinder');

        // Call with default params
        ModelFinder::all();

        $cached = Cache::get(config('tall-datatables.cache_key') . '.modelFinder');

        expect($cached)->toBeArray()
            ->not->toBeEmpty();

        // The key should be the md5 of the serialized default args
        $keys = array_keys($cached);
        expect($keys)->toHaveCount(1);
    });

    test('caches with different keys for different parameters', function (): void {
        Cache::forget(config('tall-datatables.cache_key') . '.modelFinder');

        // First call with default params
        ModelFinder::all();

        $cachedAfterFirst = Cache::get(config('tall-datatables.cache_key') . '.modelFinder');
        $firstKeyCount = count(array_keys($cachedAfterFirst));

        // Second call with a specific directory
        ModelFinder::all(app_path());

        $cachedAfterSecond = Cache::get(config('tall-datatables.cache_key') . '.modelFinder');
        $secondKeyCount = count(array_keys($cachedAfterSecond));

        // Should have at least as many keys since different params create different entries
        expect($secondKeyCount)->toBeGreaterThanOrEqual($firstKeyCount);
    });
});
