<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use TeamNiftyGmbH\DataTable\Exceptions\MissingTraitException;

trait SupportsCache
{
    #[Locked]
    public ?string $cacheKey = null;

    public function mountSupportsCache(): void
    {
        if (config('tall-datatables.should_cache')) {
            $cachedFilters = Session::get(config('tall-datatables.cache_key') . '.filter:' . $this->getCacheKey());
        } else {
            $cachedFilters = null;
        }

        if (! is_null($cachedFilters)) {
            $this->loadFilter($cachedFilters);
        }
    }

    protected function cacheState(): void
    {
        $filter = [
            'userFilters' => $this->userFilters,
            'enabledCols' => $this->enabledCols,
            'aggregatableCols' => $this->aggregatableCols,
            'userOrderBy' => $this->userOrderBy,
            'userOrderAsc' => $this->userOrderAsc,
            'perPage' => $this->perPage,
            'page' => $this->page,
            'search' => $this->search,
            'selected' => $this->selected,
        ];

        if (config('tall-datatables.should_cache')) {
            Session::put(config('tall-datatables.cache_key') . '.filter:' . $this->getCacheKey(), $filter);
        }

        try {
            $this->ensureAuthHasTrait();

            Auth::user()->datatableUserSettings()->updateOrCreate(
                [
                    'cache_key' => $this->getCacheKey(),
                    'is_layout' => true,
                ],
                $this->compileStoredLayout()
            );
        } catch (MissingTraitException) {
        }
    }

    #[Renderless]
    public function getCacheKey(): string
    {
        return $this->cacheKey ?: get_called_class();
    }

    protected function compileStoredLayout(): array
    {
        return [
            'name' => 'layout',
            'cache_key' => $this->getCacheKey(),
            'component' => static::class,
            'settings' => [
                'userFilters' => [],
                'enabledCols' => $this->enabledCols,
                'aggregatableCols' => $this->aggregatableCols,
                'perPage' => $this->perPage,
            ],
            'is_layout' => true,
        ];
    }
}
