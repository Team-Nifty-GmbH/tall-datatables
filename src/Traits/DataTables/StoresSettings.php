<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Renderless;
use TeamNiftyGmbH\DataTable\Exceptions\MissingTraitException;
use TeamNiftyGmbH\DataTable\Traits\HasDatatableUserSettings;

trait StoresSettings
{
    public array $savedFilters = [];

    public bool $showSavedFilters = true;

    public function mountStoresSettings(): void
    {
        $this->savedFilters = $this->getSavedFilters();
        $savedFilters = collect($this->savedFilters);

        $permanentFilter = $savedFilters->where('is_permanent', true)->first();
        $layoutFilter = $savedFilters->where('is_layout', true)->first();

        if (! is_null($layoutFilter) || ! is_null($permanentFilter)) {
            $this->loadFilter(data_get($permanentFilter ?? $layoutFilter, 'settings'));
            $this->loadedFilterId = data_get($permanentFilter, 'id') ?? data_get($layoutFilter, 'id');
        }
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

    /**
     * @throws MissingTraitException
     */
    #[Renderless]
    public function saveFilter(string $name, bool $permanent = false): void
    {
        $this->ensureAuthHasTrait();

        if ($permanent) {
            Auth::user()->datatableUserSettings()->update(['is_permanent' => false]);
        }

        Auth::user()->datatableUserSettings()->create([
            'name' => $name,
            'component' => get_class($this),
            'cache_key' => $this->getCacheKey(),
            'settings' => [
                'enabledCols' => $this->enabledCols,
                'aggregatableCols' => $this->aggregatableCols,
                'userFilters' => $this->userFilters,
                'userOrderBy' => $this->userOrderBy,
                'userOrderAsc' => $this->userOrderAsc,
                'perPage' => $this->perPage,
            ],
            'is_permanent' => $permanent,
        ]);
    }

    /**
     * @throws MissingTraitException
     */
    #[Renderless]
    public function deleteSavedFilter(string $id): void
    {
        $this->ensureAuthHasTrait();

        Auth::user()->datatableUserSettings()->whereKey($id)->delete();
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

            $this->reset(array_keys(array_merge($layout?->settings ?? [], $cached)));
            $this->loadData();
        } catch (MissingTraitException) {
        }
    }

    #[Renderless]
    public function getSavedFilters(): array
    {
        if (Auth::user() && method_exists(Auth::user(), 'getDataTableSettings')) {
            return Auth::user()
                ->getDataTableSettings($this)
                ?->toArray() ?? [];
        } else {
            return [];
        }
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
