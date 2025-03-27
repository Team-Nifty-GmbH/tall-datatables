<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Renderless;
use TeamNiftyGmbH\DataTable\Exceptions\MissingTraitException;
use TeamNiftyGmbH\DataTable\Traits\HasDatatableUserSettings;

trait StoresSettings
{
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
        data_forget($savedFilter, 'enabledCols');

        Auth::user()->datatableUserSettings()->whereKey($id)->update(['settings' => $savedFilter]);

        $this->savedFilters = $this->getSavedFilters(
            fn (Builder $query) => $query->where('is_layout', false)
        );
    }

    #[Renderless]
    public function getSavedFilters(?Closure $filter = null): array
    {
        if (Auth::user() && method_exists(Auth::user(), 'getDataTableSettings')) {
            return Auth::user()
                ->getDataTableSettings($this, $filter)
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ?->toArray() ?? [];
        } else {
            return [];
        }
    }

    #[Renderless]
    public function loadSavedFilter(): void
    {
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
    }

    #[Renderless]
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
            Auth::user()->datatableUserSettings()->update(['is_permanent' => false]);
        }

        Auth::user()->datatableUserSettings()->create([
            'name' => $name,
            'component' => get_class($this),
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

    #[Renderless]
    public function storeColLayout(array $cols): void
    {
        $reload = count($cols) > count($this->enabledCols);

        $this->enabledCols = $cols;

        $this->cacheState();
        if ($reload) {
            $this->loadData();
        }
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
     * @throws MissingTraitException
     */
    protected function ensureAuthHasTrait(): void
    {
        if (! Auth::user() || ! in_array(HasDatatableUserSettings::class, class_uses_recursive(Auth::user()))) {
            throw MissingTraitException::create(Auth::user()?->getMorphClass(), HasDatatableUserSettings::class);
        }
    }
}
