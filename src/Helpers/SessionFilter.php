<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Closure;
use Illuminate\Support\Facades\Cache;
use Laravel\SerializableClosure\SerializableClosure;
use TeamNiftyGmbH\DataTable\DataTable;
use Throwable;

class SessionFilter
{
    public function __construct(
        public string|DataTable|null $dataTableCacheKey = null,
        public Closure|SerializableClosure|null $closure = null,
        public ?string $name = null,
        public bool $loaded = false
    ) {
        $this->setDataTableCacheKey($this->dataTableCacheKey);
        $this->setClosure($this->closure);
    }

    public static function forget(string $dataTableCacheKey): void
    {
        Cache::forget(static::cacheKey($dataTableCacheKey));
        session()->forget($dataTableCacheKey . '_query');
    }

    public static function make(string|DataTable $dataTableCacheKey, Closure $closure, string $name): static
    {
        return new static($dataTableCacheKey, $closure, $name);
    }

    public static function retrieve(string $dataTableCacheKey): ?static
    {
        $cacheKey = static::cacheKey($dataTableCacheKey);

        $raw = Cache::get($cacheKey);

        if (! is_string($raw)) {
            return null;
        }

        try {
            $filter = unserialize($raw);

            return $filter instanceof static ? $filter : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function cacheKey(string $dataTableCacheKey): string
    {
        $userKey = auth()->check()
            ? auth()->user()->getMorphClass() . ':' . auth()->id()
            : session()->getId();

        return 'session_filter:' . $userKey . ':' . $dataTableCacheKey;
    }

    public function getClosure(): Closure
    {
        return $this->closure->getClosure();
    }

    public function setClosure(callable $closure): static
    {
        $this->closure = $closure instanceof SerializableClosure
            ? $closure
            : new SerializableClosure($closure);

        return $this;
    }

    public function setDataTableCacheKey(string|DataTable $dataTableCacheKey): static
    {
        $this->dataTableCacheKey = $dataTableCacheKey instanceof DataTable
            ? $dataTableCacheKey->getCacheKey()
            : $dataTableCacheKey;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function store(): void
    {
        $cacheKey = static::cacheKey($this->dataTableCacheKey);

        Cache::put($cacheKey, serialize($this), now()->addHours(2));

        session()->put($this->dataTableCacheKey . '_query', true);
    }
}
