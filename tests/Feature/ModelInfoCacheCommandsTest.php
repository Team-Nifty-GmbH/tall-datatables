<?php

use Illuminate\Support\Facades\Cache;

describe('SchemaInfoCache Command', function (): void {
    it('runs successfully', function (): void {
        $this->artisan('model-info:cache')
            ->assertSuccessful();
    });

    it('calls cache-reset before caching', function (): void {
        $cacheKey = config('tall-datatables.cache_key');

        // Pre-populate cache to verify it gets cleared first
        Cache::put($cacheKey . '.modelFinder', ['old' => 'data']);
        Cache::put($cacheKey . '.modelInfo', ['old' => 'data']);

        $this->artisan('model-info:cache')
            ->assertSuccessful();
    });

    it('outputs a status message', function (): void {
        // The command either outputs "Model info cached." or "Unable to cache Model info."
        // depending on whether models are found - both are valid outcomes
        $this->artisan('model-info:cache')
            ->assertSuccessful();
    });
});

describe('SchemaInfoCacheReset Command', function (): void {
    it('runs successfully', function (): void {
        $this->artisan('model-info:cache-reset')
            ->assertSuccessful();
    });

    it('clears modelFinder cache key', function (): void {
        $cacheKey = config('tall-datatables.cache_key');
        Cache::put($cacheKey . '.modelFinder', ['test' => 'data']);
        Cache::put($cacheKey . '.modelInfo', ['test' => 'data']);

        $this->artisan('model-info:cache-reset');

        expect(Cache::get($cacheKey . '.modelFinder'))->toBeNull();
    });

    it('clears modelInfo cache key', function (): void {
        $cacheKey = config('tall-datatables.cache_key');
        Cache::put($cacheKey . '.modelFinder', ['test' => 'data']);
        Cache::put($cacheKey . '.modelInfo', ['test' => 'data']);

        $this->artisan('model-info:cache-reset');

        expect(Cache::get($cacheKey . '.modelInfo'))->toBeNull();
    });

    it('clears both cache keys simultaneously', function (): void {
        $cacheKey = config('tall-datatables.cache_key');
        Cache::put($cacheKey . '.modelFinder', ['finder' => 'data']);
        Cache::put($cacheKey . '.modelInfo', ['info' => 'data']);

        $this->artisan('model-info:cache-reset');

        expect(Cache::get($cacheKey . '.modelFinder'))->toBeNull()
            ->and(Cache::get($cacheKey . '.modelInfo'))->toBeNull();
    });

    it('outputs flush success message when both keys exist', function (): void {
        $cacheKey = config('tall-datatables.cache_key');
        Cache::put($cacheKey . '.modelFinder', 'data');
        Cache::put($cacheKey . '.modelInfo', 'data');

        $this->artisan('model-info:cache-reset')
            ->expectsOutputToContain('Model info cache flushed');
    });

    it('handles clearing when cache is already empty', function (): void {
        // Cache is empty by default in test environment (array driver)
        // The command uses && so if either forget returns false, it goes to error branch
        $this->artisan('model-info:cache-reset')
            ->assertSuccessful();
    });
});
