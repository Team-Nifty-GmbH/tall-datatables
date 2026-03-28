<?php

use Illuminate\Support\Facades\Cache;

describe('ModelInfoCacheReset Command', function (): void {
    test('model-info:cache-reset command can be called', function (): void {
        $this->artisan('model-info:cache-reset')
            ->assertSuccessful();
    });

    test('model-info:cache-reset clears model info cache', function (): void {
        $cacheKey = config('tall-datatables.cache_key');
        Cache::put($cacheKey . '.modelFinder', ['test' => 'data']);
        Cache::put($cacheKey . '.modelInfo', ['test' => 'data']);

        $this->artisan('model-info:cache-reset');

        expect(Cache::get($cacheKey . '.modelFinder'))->toBeNull();
        expect(Cache::get($cacheKey . '.modelInfo'))->toBeNull();
    });
});
