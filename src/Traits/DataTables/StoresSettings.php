<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use TeamNiftyGmbH\DataTable\Exceptions\MissingTraitException;
use TeamNiftyGmbH\DataTable\Traits\HasDatatableUserSettings;

trait StoresSettings
{
    #[Locked]
    public ?string $cacheKey = null;

    public array $savedFilters = [];

    public bool $showSavedFilters = true;

    /**
     * @throws MissingTraitException
     */
    #[Renderless]
    public function deleteSavedFilter(string $id): void
    {
        $this->ensureAuthHasTrait();

        Auth::user()->datatableUserSettings()->whereKey($id)->delete();

        $this->savedFilters = $this->getSavedFilters(
            fn (Builder $query) => $query->where('is_layout', false)
        );
    }

    #[Renderless]
    public function deleteSavedFilterEnabledCols(int $id): void
    {
        $savedFilter = Auth::user()->datatableUserSettings()->whereKey($id)->value('settings');

        if (is_null($savedFilter)) {
            return;
        }

        data_forget($savedFilter, 'enabledCols');

        Auth::user()->datatableUserSettings()->whereKey($id)->update(['settings' => $savedFilter]);

        $this->savedFilters = $this->getSavedFilters(
            fn (Builder $query) => $query->where('is_layout', false)
        );
    }

    #[Renderless]
    public function getCacheKey(): string
    {
        return $this->cacheKey ?: static::class;
    }

    #[Renderless]
    public function getSavedFilters(?Closure $filter = null): array
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'getDataTableSettings')) {
            return $user
                ->getDataTableSettings($this, $filter)
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->toArray();
        }

        return [];
    }

    public function loadSavedFilter(): void
    {
        $this->loadingFilter = true;

        $this->loadFilter(
            data_get(
                collect($this->savedFilters)->where('id', $this->loadedFilterId)->first(),
                'settings',
                []
            )
        );
    }

    public function mountStoresSettings(): void
    {
        $this->savedFilters = $this->getSavedFilters(
            fn (Builder $query) => $query->where('is_layout', false)
        );
        $savedFilters = collect($this->savedFilters);

        $permanentFilter = $savedFilters->where('is_permanent', true)->first();
        $layoutFilter = data_get($this->getSavedFilters(
            fn (Builder $query) => $query->where('is_layout', true)
        ), 0);

        if (! is_null($layoutFilter) || ! is_null($permanentFilter)) {
            $this->loadFilter(data_get($permanentFilter ?? $layoutFilter, 'settings'));
            $this->loadedFilterId = data_get($permanentFilter, 'id') ?? data_get($layoutFilter, 'id');
        }

        // Load cached session state
        if (config('tall-datatables.should_cache')) {
            $cachedFilters = Session::get(config('tall-datatables.cache_key') . '.filter:' . $this->getCacheKey());

            if (! is_null($cachedFilters)) {
                $this->loadFilter($cachedFilters);
            }
        }

        // Regenerate labels after enabledCols may have changed from cache/saved filters
        $this->colLabels = $this->getColLabels();
    }

    public function resetLayout(): void
    {
        try {
            $this->ensureAuthHasTrait();
            $layout = Auth::user()
                ->datatableUserSettings()
                ->where('component', $this->getCacheKey())
                ->where('is_layout', true)
                ->first();
            $cached = Session::get(config('tall-datatables.cache_key') . '.filter:' . $this->getCacheKey());

            if (! $layout && ! $cached) {
                return;
            }

            $layout?->delete();
            Session::remove(config('tall-datatables.cache_key') . '.filter:' . $this->getCacheKey());

            $this->reset(array_keys(array_merge($layout?->settings ?? [], $cached ?? [])));
            $this->colLabels = $this->getColLabels();
            $this->loadData();
        } catch (MissingTraitException) {
        }
    }

    /**
     * @throws MissingTraitException
     */
    #[Renderless]
    public function saveFilter(string $name, bool $permanent = false, bool $withEnabledCols = true): void
    {
        $this->ensureAuthHasTrait();

        if ($permanent) {
            Auth::user()->datatableUserSettings()
                ->where('cache_key', $this->getCacheKey())
                ->update(['is_permanent' => false]);
        }

        Auth::user()->datatableUserSettings()->create([
            'name' => $name,
            'component' => static::class,
            'cache_key' => $this->getCacheKey(),
            'settings' => array_merge(
                [
                    'aggregatableCols' => $this->aggregatableCols,
                    'userFilters' => $this->userFilters,
                    'userOrderBy' => $this->userOrderBy,
                    'userOrderAsc' => $this->userOrderAsc,
                    'perPage' => $this->perPage,
                ],
                $withEnabledCols ? ['enabledCols' => $this->enabledCols] : []
            ),
            'is_permanent' => $permanent,
        ]);

        $this->savedFilters = $this->getSavedFilters(
            fn (Builder $query) => $query->where('is_layout', false)
        );
    }

    public function storeColLayout(array $cols): void
    {
        $this->enabledCols = $cols;
        $this->colLabels = $this->getColLabels();

        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function updateSavedFilter($filterID, array $data): void
    {
        Auth::user()
            ->datatableUserSettings()
            ->whereKey($filterID)
            ->update(Arr::only($data, ['name']));
    }

    /**
     * Cache the current state to session and database.
     */
    protected function cacheState(): void
    {
        $filter = [
            'userFilters' => $this->userFilters,
            'enabledCols' => $this->enabledCols,
            'aggregatableCols' => $this->aggregatableCols,
            'userOrderBy' => $this->userOrderBy,
            'userOrderAsc' => $this->userOrderAsc,
            'perPage' => $this->perPage,
            'search' => $this->search,
            'selected' => $this->selected,
            'groupBy' => $this->groupBy,
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

    /**
     * Compile the layout settings for database storage.
     */
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

    /**
     * @throws MissingTraitException
     */
    protected function ensureAuthHasTrait(): void
    {
        if (! Auth::user() || ! in_array(HasDatatableUserSettings::class, class_uses_recursive(Auth::user()))) {
            throw MissingTraitException::create(Auth::user()?->getMorphClass(), HasDatatableUserSettings::class);
        }
    }
}
