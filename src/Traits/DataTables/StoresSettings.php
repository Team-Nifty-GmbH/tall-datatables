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

    #[Renderless]
    public function deleteDefaultColumns(): void
    {
        if (! $this->canSaveDefaultColumns()) {
            return;
        }

        $this->ensureAuthHasTrait();

        $settingModel = config('tall-datatables.models.datatable_user_setting');
        $settingModel::query()
            ->where('component', static::class)
            ->where('cache_key', $this->getCacheKey())
            ->where('is_default_columns', true)
            ->delete();
    }

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

        if (! $user || ! method_exists($user, 'datatableUserSettings')) {
            return [];
        }

        $settingModel = config('tall-datatables.models.datatable_user_setting');
        $cacheKey = $this->getCacheKey();

        $query = $settingModel::query()
            ->where('component', static::class)
            ->where('cache_key', $cacheKey)
            ->where(function ($q) use ($user): void {
                $q->where(function ($own) use ($user): void {
                    $own->where('authenticatable_id', $user->getKey())
                        ->where('authenticatable_type', $user->getMorphClass());
                });

                if ($this->canShareFilters()) {
                    $q->orWhere(function ($shared) use ($user): void {
                        $shared->where('is_shared', true)
                            ->where('authenticatable_type', $user->getMorphClass());
                    });
                }
            });

        if ($filter) {
            $filter($query);
        }

        $results = $query->orderByRaw('LOWER(name)')->get()->toArray();

        $userId = $user->getKey();
        $userType = $user->getMorphClass();

        return array_map(function (array $filter) use ($userId, $userType): array {
            $filter['is_own'] = $filter['authenticatable_id'] == $userId
                && $filter['authenticatable_type'] === $userType;

            return $filter;
        }, $results);
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
        // Load global default columns (applies to all users, lowest priority override)
        $settingModel = config('tall-datatables.models.datatable_user_setting');
        $defaultColumns = $settingModel::query()
            ->where('component', static::class)
            ->where('cache_key', $this->getCacheKey())
            ->where('is_default_columns', true)
            ->first();

        if ($defaultColumns) {
            $this->enabledCols = data_get($defaultColumns->settings, 'enabledCols', $this->enabledCols);
        }

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

        // Migrate old flat textFilters format to grouped format
        $this->migrateTextFiltersIfNeeded();

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

            $layout?->delete();
            Session::remove(config('tall-datatables.cache_key') . '.filter:' . $this->getCacheKey());

            $resetKeys = array_keys(array_merge($layout?->settings ?? [], $cached ?? []));
            if (! in_array('enabledCols', $resetKeys)) {
                $resetKeys[] = 'enabledCols';
            }
            $this->reset($resetKeys);

            // Restore global default columns if available
            $settingModel = config('tall-datatables.models.datatable_user_setting');
            $defaultColumns = $settingModel::query()
                ->where('component', static::class)
                ->where('cache_key', $this->getCacheKey())
                ->where('is_default_columns', true)
                ->first();

            if ($defaultColumns) {
                $this->enabledCols = data_get($defaultColumns->settings, 'enabledCols', $this->enabledCols);
            }

            $this->colLabels = $this->getColLabels();
            $this->loadData();
        } catch (MissingTraitException) {
        }
    }

    #[Renderless]
    public function saveDefaultColumns(): void
    {
        if (! $this->canSaveDefaultColumns()) {
            return;
        }

        $this->ensureAuthHasTrait();

        $settingModel = config('tall-datatables.models.datatable_user_setting');

        $settingModel::updateOrCreate(
            [
                'component' => static::class,
                'cache_key' => $this->getCacheKey(),
                'is_default_columns' => true,
            ],
            [
                'name' => '__default_columns__',
                'settings' => ['enabledCols' => $this->enabledCols],
                'is_layout' => false,
                'is_permanent' => false,
                'authenticatable_id' => Auth::id(),
                'authenticatable_type' => Auth::user()->getMorphClass(),
            ]
        );
    }

    /**
     * @throws MissingTraitException
     */
    #[Renderless]
    public function saveFilter(string $name, bool $permanent = false, bool $withEnabledCols = true, bool $isShared = false): void
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
                    'textFilters' => $this->textFilters,
                    'userFilters' => $this->userFilters,
                    'userOrderBy' => $this->userOrderBy,
                    'userOrderAsc' => $this->userOrderAsc,
                    'userMultiSort' => $this->userMultiSort,
                    'perPage' => $this->perPage,
                    'activeLayout' => $this->activeLayout,
                ],
                $withEnabledCols ? ['enabledCols' => $this->enabledCols] : []
            ),
            'is_permanent' => $permanent,
            'is_shared' => $this->canShareFilters() && $isShared,
        ]);

        $this->savedFilters = $this->getSavedFilters(
            fn (Builder $query) => $query->where('is_layout', false)
        );
    }

    #[Renderless]
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
        $allowed = ['name'];

        if ($this->canShareFilters()) {
            $allowed[] = 'is_shared';
        }

        Auth::user()
            ->datatableUserSettings()
            ->whereKey($filterID)
            ->update(Arr::only($data, $allowed));
    }

    /**
     * Cache the current state to session and database.
     */
    protected function cacheState(): void
    {
        $filter = [
            'textFilters' => $this->textFilters,
            'userFilters' => $this->userFilters,
            'enabledCols' => $this->enabledCols,
            'aggregatableCols' => $this->aggregatableCols,
            'userOrderBy' => $this->userOrderBy,
            'userOrderAsc' => $this->userOrderAsc,
            'userMultiSort' => $this->userMultiSort,
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

    protected function canSaveDefaultColumns(): bool
    {
        return false;
    }

    protected function canShareFilters(): bool
    {
        return false;
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
                'activeLayout' => $this->activeLayout,
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
