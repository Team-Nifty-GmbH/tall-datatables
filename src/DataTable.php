<?php

namespace TeamNiftyGmbH\DataTable;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Spatie\ModelInfo\Attributes\Attribute;
use TallStackUi\Traits\Interactions;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;
use TeamNiftyGmbH\DataTable\Traits\DataTables\BuildsQueries;
use TeamNiftyGmbH\DataTable\Traits\DataTables\StoresSettings;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsAggregation;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsExporting;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsGrouping;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsRelations;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsSelecting;
use Throwable;

class DataTable extends Component
{
    use BuildsQueries, Interactions, StoresSettings, SupportsAggregation, SupportsExporting, SupportsGrouping,
        SupportsRelations, SupportsSelecting;

    public array $appends = [];

    #[Locked]
    public array $availableCols = ['*'];

    #[Locked]
    public array $availableRelations = ['*'];

    public array $colLabels = [];

    public array $columnLabels = [];

    public array $data = [];

    public array $enabledCols = [];

    #[Locked]
    public array $filters = [];

    public array $filterValueLists = [];

    public array $formatters = [];

    #[Locked]
    public bool $hasHead = true;

    public bool $hasInfiniteScroll = false;

    public bool $hasNoRedirect = false;

    #[Locked]
    public bool $hasSidebar = true;

    public bool $hasStickyCols = true;

    public string $headline = '';

    public bool $initialized = false;

    #[Locked]
    public bool $isFilterable = true;

    public ?bool $isSearchable = null;

    public ?int $loadedFilterId = null;

    public bool $loadingFilter = false;

    #[Locked]
    public ?string $modelKeyName = null;

    #[Locked]
    public ?string $modelTable = null;

    public bool $orderAsc = true;

    public string $orderBy = '';

    public int $page = 1;

    public int $perPage = 15;

    public string $search = '';

    public array $sessionFilter = [];

    public bool $showFilterInputs = true;

    public array $sortable = ['*'];

    public array $stickyCols = [];

    public array $textFilters = [];

    public array $userFilters = [];

    public bool $userOrderAsc = true;

    public string $userOrderBy = '';

    public bool $withSoftDeletes = false;

    protected ?string $includeAfter = null;

    protected ?string $includeBefore = null;

    protected $listeners = ['dataTableReload' => 'reloadData'];

    protected string $model;

    protected bool $useWireNavigate = true;

    protected string $view = 'tall-datatables::livewire.data-table';

    private ?array $cachedActions = null;

    /** @internal Cached per-request to avoid repeated expensive computation */
    private ?array $cachedViewData = null;

    private bool $dataLoadedThisRequest = false;

    public function mount(): void
    {
        if (! $this->modelKeyName || ! $this->modelTable) {
            $model = app($this->getModel());
            $this->modelKeyName = $this->modelKeyName ?: $model->getKeyName();
            $this->modelTable = $this->modelTable ?: $model->getTable();
        }

        $this->colLabels = $this->getColLabels();
        $this->loadData();
    }

    public function render(): View|Factory|Application|null
    {
        if (! $this->initialized) {
            $this->loadData();
        }

        return view($this->getView(), $this->getViewData());
    }

    /**
     * Clear data and per-request caches before sending to client.
     * Data is only needed during render, not in the snapshot.
     */
    public function dehydrate(): void
    {
        $this->data = [];
        $this->cachedViewData = null;
        $this->cachedActions = null;
        $this->dataLoadedThisRequest = false;
    }

    protected function getTableActions(): array
    {
        return [];
    }

    #[Renderless]
    public function applyUserFilters(): void
    {
        // Sync textFilters with userFilters (sidebar may have added/removed text-source filters)
        $activeTextCols = [];
        foreach ($this->userFilters as $groupIndex => $group) {
            if (! is_array($group)) {
                continue;
            }

            foreach ($group as $filter) {
                if (($filter['source'] ?? '') === 'text') {
                    $activeTextCols[$groupIndex][$filter['column']] = true;
                }
            }
        }

        // Remove textFilters entries that are no longer in userFilters
        foreach ($this->textFilters as $groupIndex => $filtersInGroup) {
            if (! is_array($filtersInGroup)) {
                continue;
            }

            foreach (array_keys($filtersInGroup) as $col) {
                if (! isset($activeTextCols[$groupIndex][$col])) {
                    unset($this->textFilters[$groupIndex][$col]);
                }
            }

            if (empty($this->textFilters[$groupIndex])) {
                unset($this->textFilters[$groupIndex]);
            }
        }

        $this->colLabels = $this->getColLabels();
        $this->loadedFilterId = null;
        $this->startSearch();
    }

    #[Renderless]
    public function clearFiltersAndSort(): void
    {
        $this->userFilters = [];
        $this->textFilters = [];
        $this->userOrderBy = '';
        $this->userOrderAsc = true;
        $this->search = '';
        $this->groupBy = null;
        $this->loadedFilterId = null;
        $this->startSearch();
    }

    /**
     * @deprecated No longer needed - v2 never skips render
     */
    public function forceRender(): void
    {
        // v2: kept for backwards compatibility but no-ops
    }

    public function forgetSessionFilter(bool $loadData = false): void
    {
        session()->forget($this->getCacheKey() . '_query');
        $this->sessionFilter = [];

        if ($loadData) {
            $this->loadData();
        }
    }

    public function formatFilterBadgeValue(string $column, string $value): string
    {
        $registry = app(Formatters\FormatterRegistry::class);
        $formatterKey = $this->getFormatters()[$column] ?? null;

        if (! $formatterKey) {
            $casts = app($this->getModel())->getCasts();
            $castValue = $casts[$column] ?? null;
            $formatterKey = is_array($castValue) ? ($castValue[0] ?? null) : $castValue;
        }

        if (! $formatterKey) {
            return $value;
        }

        try {
            $formatter = $registry->resolve($formatterKey);
            $numericValue = is_numeric($value) ? (float) $value : $value;
            $formatted = $formatter->format($numericValue, []);

            return strip_tags(is_array($formatted) ? ($formatted['display'] ?? $formatted['raw'] ?? $value) : $formatted);
        } catch (Throwable) {
            return $value;
        }
    }

    #[Renderless]
    public function getAvailableCols(): array
    {
        $availableCols = $this->availableCols === ['*']
            ? ModelInfo::forModel($this->getModel())->attributes->pluck('name')->toArray()
            : $this->availableCols;

        return array_values(array_unique(array_merge(
            $this->enabledCols, $availableCols, [$this->modelKeyName]
        )));
    }

    #[Renderless]
    public function getColLabels(?array $cols = null): array
    {
        $colLabels = array_flip(
            $cols ?: array_merge(
                $this->enabledCols,
                $this->getAggregatable(),
                $this->getGroupableCols(),
                array_filter(
                    Arr::dot($this->userFilters),
                    fn ($key) => str_ends_with($key, '.column'),
                    ARRAY_FILTER_USE_KEY
                )
            )
        );

        array_walk($colLabels, function (&$value, $key): void {
            if (str_contains($key, '.') && ! ($this->columnLabels[$key] ?? false)) {
                $relation = explode('.', Str::beforeLast($key, '.'));
                $column = Str::afterLast($key, '.');
                $relation = array_map(fn ($part) => __(Str::headline($part)), $relation);
                $value = implode(' -> ', $relation) . ' -> ' . __(Str::headline($column));
            } else {
                $value = __(Str::headline($this->columnLabels[$key] ?? $key));
            }
        });

        return $colLabels;
    }

    #[Renderless]
    public function getConfig(): array
    {
        return [
            'enabledCols' => $this->getEnabledCols(),
            'availableCols' => $this->getAvailableCols(),
            'colLabels' => $this->getColLabels(),
            'selectable' => $this->isSelectable,
            'aggregatable' => $this->getAggregatable(),
            'groupable' => $this->getGroupableCols(),
            'formatters' => $this->getFormatters(),
            'leftAppend' => $this->getLeftAppends(),
            'rightAppend' => $this->getRightAppends(),
            'topAppend' => $this->getTopAppends(),
            'bottomAppend' => $this->getBottomAppends(),
            'searchRoute' => $this->getSearchRoute(),
            'operatorLabels' => $this->getOperatorLabels(),
            'groupLabels' => $this->getGroupLabels(),
        ];
    }

    /**
     * Re-populate data for assertions in tests. Only call after loadData().
     */
    public function getDataForTesting(): array
    {
        if (empty($this->data) && $this->initialized) {
            $this->loadData();
        }

        return $this->data;
    }

    #[Renderless]
    public function getFormatters(): array
    {
        $formatters = [];
        foreach ($this->getIncludedRelations() as $loadedRelation) {
            $relationFormatters = method_exists($loadedRelation['model'], 'typeScriptAttributes')
                ? $loadedRelation['model']::typeScriptAttributes()
                : app($loadedRelation['model'])->getCasts();
            foreach ($loadedRelation['loaded_columns'] as $loadedColumn) {
                $formatters[$loadedColumn['loaded_as']] = $relationFormatters[$loadedColumn['column']] ?? null;
            }
        }

        return array_filter(array_merge($formatters, $this->formatters));
    }

    #[Renderless]
    public function getGroupLabels(): array
    {
        return [
            'entries' => __('entry') . '|' . __('entries'),
            'showing' => __('Showing'), 'to' => __('to'), 'of' => __('of'),
            'groups' => __('Groups'), 'noGrouping' => __('No grouping'),
            'empty' => __('(empty)'),
            'sum' => __('Sum'), 'avg' => __('Avg'), 'min' => __('Min'), 'max' => __('Max'),
        ];
    }

    public function getIslandData(): array
    {
        return $this->getViewData();
    }

    #[Renderless]
    public function getOperatorLabels(): array
    {
        return [
            'like' => __('like'), 'not like' => __('not like'),
            'is null' => __('is null'), 'is not null' => __('is not null'),
            'between' => __('between'), 'and' => __('and'),
            'Now' => __('Now'),
            'minutes' => __('Minutes'), 'hours' => __('Hours'), 'days' => __('Days'),
            'weeks' => __('Weeks'), 'months' => __('Months'), 'years' => __('Years'),
            'minute' => __('Minute'), 'hour' => __('Hour'), 'day' => __('Day'),
            'week' => __('Week'), 'month' => __('Month'), 'year' => __('Year'),
            'sum' => __('Sum'), 'avg' => __('Average'), 'min' => __('Minimum'), 'max' => __('Maximum'),
            'Start of' => __('Start of'), 'End of' => __('End of'),
        ];
    }

    /**
     * @deprecated Use getActiveFilters() instead
     */
    public function getParsedTextFilters(): \Illuminate\Support\Collection
    {
        return collect($this->userFilters)
            ->flatten(1)
            ->filter(fn ($f) => is_array($f) && ($f['source'] ?? '') === 'text')
            ->map(function ($filter) {
                $displayValue = $filter['value'] ?? '';

                // Translate enum/state values for display
                if ($filter['operator'] === '=' && isset($this->filterValueLists[$filter['column']])) {
                    $label = collect($this->filterValueLists[$filter['column']])
                        ->firstWhere('value', $displayValue);
                    $displayValue = $label['label'] ?? $displayValue;
                }

                // Strip LIKE wildcards for display
                if ($filter['operator'] === 'like' && is_string($displayValue)) {
                    $displayValue = trim($displayValue, '%');
                }

                return [
                    'column' => $filter['column'],
                    'operator' => $filter['operator'],
                    'value' => $displayValue,
                ];
            })
            ->values();
    }

    #[Renderless]
    public function gotoPage(int $page): void
    {
        $this->page = $page;
        $this->cacheState();
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->initialized = true;
        $this->dataLoadedThisRequest = true;
        $this->resolvedFormatters = null;
        $this->cachedViewData = null;

        // Islands handle the DOM update — skip the full component re-render
        // to avoid sending 600KB+ of unchanged sidebar/modal HTML.
        // Full render is needed when non-island parts (like thead) must update.
        if (request()->isMethod('POST')) {
            $this->skipRender();
        }

        $query = $this->buildSearch();
        $baseQuery = $query->clone();

        if ($this->isGrouped()) {
            $groupedData = $this->loadGroupedData($query);

            $this->setData([
                'groups' => $groupedData['groups'],
                'groups_pagination' => $groupedData['groups_pagination'],
                'data' => [],
                'total' => array_sum(array_column($groupedData['groups'], 'count')),
            ]);

            if ($aggregates = $this->getAggregate($baseQuery)) {
                $this->data['aggregates'] = ! $this->search
                    ? $this->formatAggregates($aggregates)
                    : [];
            }

            $this->renderIsland('body');
            $this->renderIsland('footer');
            $this->renderIsland('badges');

            return;
        }

        $result = $this->getResultFromQuery($query);

        $resultIsEmpty = $result instanceof LengthAwarePaginator
            ? $result->isEmpty()
            : collect($result)->isEmpty();

        if ($resultIsEmpty && $this->page > 1) {
            $this->page = 1;
            $this->loadData();

            return;
        }

        $this->setData(is_array($result) ? $result : $result->toArray());

        if (in_array('*', $this->selected)) {
            $this->selected = array_diff(
                array_column($this->data['data'] ?? $this->data, $this->modelKeyName),
                $this->wildcardSelectExcluded
            );
            $this->selected[] = '*';
            $this->selected = array_values($this->selected);
        }

        if ($aggregates = $this->getAggregate($baseQuery)) {
            $this->data['aggregates'] = ! $this->search
                ? $this->formatAggregates($aggregates)
                : [];
        }

        if ($this->data['links'] ?? false) {
            array_pop($this->data['links']);
            array_shift($this->data['links']);
        }

        $this->renderIsland('body');
        $this->renderIsland('footer');
        $this->renderIsland('badges');
    }

    #[Renderless]
    public function loadFilter(array $properties): void
    {
        if (! $properties) {
            return;
        }

        foreach ($properties as $property => $value) {
            $this->{$property} = $value;
        }

        if ($this->initialized) {
            $this->loadData();
        }
    }

    #[Renderless]
    public function loadMore(): void
    {
        $this->perPage = min($this->perPage * 2, 1000);
        $this->loadData();
    }

    public function placeholder(): View|Factory|Application
    {
        return view('tall-datatables::livewire.placeholder');
    }

    #[Renderless]
    public function reloadData(): void
    {
        $this->loadData();
    }

    #[Renderless]
    public function removeFilter(int $groupIndex, int $filterIndex): void
    {
        if (! isset($this->userFilters[$groupIndex][$filterIndex])) {
            return;
        }

        $filter = $this->userFilters[$groupIndex][$filterIndex];

        // If it's a text filter, also remove from textFilters
        if (($filter['source'] ?? '') === 'text') {
            foreach ($this->textFilters as $gIdx => $group) {
                if (is_array($group) && isset($group[$filter['column']])) {
                    unset($this->textFilters[$gIdx][$filter['column']]);
                    if (empty($this->textFilters[$gIdx])) {
                        unset($this->textFilters[$gIdx]);
                    }

                    break;
                }
            }
        }

        array_splice($this->userFilters[$groupIndex], $filterIndex, 1);

        // Remove empty groups
        if (empty($this->userFilters[$groupIndex])) {
            array_splice($this->userFilters, $groupIndex, 1);
        }

        $this->userFilters = array_values($this->userFilters);
        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function removeFilterGroup(int $groupIndex): void
    {
        if (! isset($this->userFilters[$groupIndex])) {
            return;
        }

        // Clean up textFilters for text-source filters in this group
        foreach ($this->userFilters[$groupIndex] as $filter) {
            if (($filter['source'] ?? '') === 'text') {
                if (isset($this->textFilters[$groupIndex][$filter['column']])) {
                    unset($this->textFilters[$groupIndex][$filter['column']]);
                }
            }
        }

        if (isset($this->textFilters[$groupIndex]) && empty($this->textFilters[$groupIndex])) {
            unset($this->textFilters[$groupIndex]);
        }

        // Remove the userFilters group before re-indexing both arrays
        // to keep textFilters and userFilters indices aligned
        array_splice($this->userFilters, $groupIndex, 1);
        $this->userFilters = array_values($this->userFilters);
        $this->textFilters = array_values($this->textFilters);
        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function removeTextFilterRow(int $groupIndex): void
    {
        $this->migrateTextFiltersIfNeeded();

        unset($this->textFilters[$groupIndex]);
        $this->textFilters = array_values($this->textFilters);

        $this->rebuildTextFilterGroup();
        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function setPerPage(int $perPage): void
    {
        if ($perPage <= 0) {
            return;
        }

        if (($this->data['total'] ?? 0) > 0 && $this->page > $this->data['total'] / $perPage) {
            $this->page = (int) ceil($this->data['total'] / $perPage);
        }

        $this->perPage = $perPage;
        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function setTextFilter(string $col, ?string $value, int $groupIndex = 0, int $valueIndex = 0): void
    {
        $this->migrateTextFiltersIfNeeded();

        if (! isset($this->textFilters[$groupIndex])) {
            $this->textFilters[$groupIndex] = [];
        }

        if ($valueIndex === 0 && ! is_array($this->textFilters[$groupIndex][$col] ?? null)) {
            // Single value (backwards compatible)
            if ($value !== null && $value !== '') {
                $this->textFilters[$groupIndex][$col] = $value;
            } else {
                unset($this->textFilters[$groupIndex][$col]);
            }
        } else {
            // Multi-value: ensure array
            $current = $this->textFilters[$groupIndex][$col] ?? [];
            if (! is_array($current)) {
                $current = [$current];
            }

            if ($value !== null && $value !== '') {
                $current[$valueIndex] = $value;
            } else {
                unset($current[$valueIndex]);
                $current = array_values($current);
            }

            if (empty($current)) {
                unset($this->textFilters[$groupIndex][$col]);
            } else {
                $this->textFilters[$groupIndex][$col] = count($current) === 1 ? $current[0] : $current;
            }
        }

        if (empty($this->textFilters[$groupIndex])) {
            unset($this->textFilters[$groupIndex]);
        }

        $this->rebuildTextFilterGroup();
        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function sortTable(string $col): void
    {
        if (! $this->isValidSortColumn($col)) {
            return;
        }

        if ($this->userOrderBy === $col) {
            $this->userOrderAsc = ! $this->userOrderAsc;
        }

        $this->userOrderBy = $col;
        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function startSearch(): void
    {
        $this->reset('selected');
        $this->page = 1;
        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function updatedSearch(): void
    {
        $this->startSearch();
    }

    #[Renderless]
    public function updatedStickyCols(): void
    {
        $this->renderIsland('body');
    }

    #[Renderless]
    public function updatedUserFilters(): void
    {
        if ($this->loadingFilter) {
            $this->loadingFilter = false;

            return;
        }

        $this->applyUserFilters();
    }

    /**
     * Hook to add computed columns (e.g. avatar) before formatters are applied.
     * Override this instead of itemToArray() to ensure formatters work on your custom columns.
     */
    protected function augmentItemArray(array &$itemArray, Model $item): void {}

    protected function compileActions(string $type): array
    {
        if (isset($this->cachedActions[$type])) {
            return $this->cachedActions[$type];
        }

        $actions = [];
        $methodBaseName = 'get' . ucfirst($type) . 'Actions';

        foreach (class_uses_recursive(static::class) as $trait) {
            $method = $methodBaseName . class_basename($trait);
            if (method_exists($this, $method)) {
                $actions = array_merge($this->$method(), $actions);
            }
        }

        if (method_exists($this, $methodBaseName)) {
            $actions = array_merge($this->{$methodBaseName}(), $actions);
        }

        $this->cachedActions[$type] = $actions;

        return $actions;
    }

    protected function formatAggregates(array $aggregates): array
    {
        $registry = app(Formatters\FormatterRegistry::class);
        $customFormatters = $this->getFormatters();
        $model = app($this->getModel());
        $modelCasts = $model->getCasts();

        foreach ($aggregates as $type => $columns) {
            foreach ($columns as $col => $value) {
                $baseCol = str_contains($col, '.') ? last(explode('.', $col)) : $col;

                if (isset($customFormatters[$col]) && is_string($customFormatters[$col])) {
                    $formatter = $registry->resolve($customFormatters[$col]);
                } elseif (isset($customFormatters[$col]) && is_array($customFormatters[$col])) {
                    $formatter = $registry->resolveWithOptions($customFormatters[$col][0] ?? 'string', $customFormatters[$col][1] ?? []);
                } else {
                    $stringCasts = array_filter($modelCasts, 'is_string');
                    $formatter = $registry->resolveForColumn($baseCol, $stringCasts);
                }

                $display = $formatter->format($value, []);
                $rawString = is_null($value) ? '' : (string) $value;

                if ($display !== e($rawString)) {
                    $aggregates[$type][$col] = ['raw' => $value, 'display' => $display];
                }
            }
        }

        return $aggregates;
    }

    protected function getAppends(): array
    {
        return $this->appends;
    }

    protected function getBottomAppends(): array
    {
        return [];
    }

    protected function getBuilder(Builder $builder): Builder
    {
        return $builder;
    }

    protected function getCellAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getComponentAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getEnabledCols(): array
    {
        return $this->enabledCols;
    }

    protected function getIncludedRelations(): array
    {
        $baseModelInfo = ModelInfo::forModel($this->getModel());
        $loadedRelations = [];

        foreach ($this->enabledCols as $enabledCol) {
            if (str_contains($enabledCol, '.')) {
                $explodedCol = explode('.', $enabledCol);
                $attribute = array_pop($explodedCol);
                $path = implode('.', array_map(fn ($relation) => Str::camel($relation), $explodedCol));
                $relation = $baseModelInfo->relation(Str::camel($path));
            } else {
                $attribute = $enabledCol;
                $path = 'self';
                $relation = null;
            }

            $loadedColumns = $loadedRelations[$path]['loaded_columns'] ?? [];
            $loadedColumns[$enabledCol] = ['column' => $attribute, 'loaded_as' => $enabledCol];
            $loadedRelations[$path] = [
                'model' => $relation?->related ?? $this->getModel(),
                'loaded_columns' => $loadedColumns,
                'type' => $relation?->type,
            ];
        }

        return $loadedRelations;
    }

    protected function getLayout(): string
    {
        return 'tall-datatables::layouts.table';
    }

    protected function getLeftAppends(): array
    {
        return [];
    }

    protected function getModel(): string
    {
        return $this->model;
    }

    protected function getRightAppends(): array
    {
        return [];
    }

    protected function getRowAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getSearchRoute(): string
    {
        return config('tall-datatables.search_route')
            ? route(config('tall-datatables.search_route'), '')
            : '';
    }

    protected function getTableFields(): \Illuminate\Support\Collection
    {
        return ModelInfo::forModel($this->getModel())
            ->attributes
            ->filter(fn (Attribute $attribute) => ! $attribute->virtual && ! $attribute->appended)
            ->when(
                $this->availableCols !== ['*'],
                fn ($attributes) => $attributes->whereIn('name', $this->availableCols)
            );
    }

    protected function getTableHeadColAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getTopAppends(): array
    {
        return [];
    }

    protected function getView(): string
    {
        return $this->view;
    }

    protected function getViewData(): array
    {
        if ($this->cachedViewData !== null) {
            return $this->cachedViewData;
        }

        $this->cachedViewData = [
            'searchable' => $this->getIsSearchable(),
            'componentAttributes' => $this->getComponentAttributes(),
            'tableHeadColAttributes' => $this->getTableHeadColAttributes(),
            'selectAttributes' => $this->getSelectAttributes(),
            'rowAttributes' => $this->getRowAttributes(),
            'cellAttributes' => $this->getCellAttributes(),
            'rowActions' => $this->compileActions('row'),
            'tableActions' => $this->compileActions('table'),
            'selectedActions' => $this->compileActions('selected'),
            'modelName' => Str::headline(class_basename($this->getModel())),
            'showFilterInputs' => $this->showFilterInputs,
            'layout' => $this->getLayout(),
            'useWireNavigate' => $this->useWireNavigate,
            'colLabels' => $this->colLabels,
            'includeBefore' => $this->includeBefore,
            'includeAfter' => $this->includeAfter,
            'selectValue' => $this->getSelectValue(),
            'allowSoftDeletes' => $this->allowSoftDeletes(),
            'showRestoreButton' => $this->showRestoreButton(),
            'aggregatable' => $this->getAggregatable(),
            'isExportable' => $this->isExportable,
        ];

        return $this->cachedViewData;
    }

    protected function setData(array $data): void
    {
        $this->data = $data;
    }

    protected function showRestoreButton(): bool
    {
        return method_exists(static::class, 'restore');
    }

    private function isValidSortColumn(string $col): bool
    {
        // Allow wildcard sortable (all columns allowed) — validate against enabled cols
        if ($this->sortable === ['*']) {
            // For dot-notation (relation sorting), allow if it's in enabledCols
            if (str_contains($col, '.')) {
                return in_array($col, $this->enabledCols);
            }

            // Validate against the model's table columns
            return in_array($col, ModelInfo::forModel($this->getModel())
                ->attributes
                ->pluck('name')
                ->toArray());
        }

        return in_array($col, $this->sortable);
    }

    private function migrateTextFiltersIfNeeded(): void
    {
        if (empty($this->textFilters)) {
            return;
        }

        // Detect old flat format: { 'column_name': 'value' } vs new: { 0: { 'column_name': 'value' } }
        $firstValue = reset($this->textFilters);
        if (is_string($firstValue) || is_null($firstValue)) {
            $this->textFilters = [array_filter($this->textFilters, fn ($v) => is_string($v) && $v !== '')];
            if (empty($this->textFilters[0])) {
                $this->textFilters = [];
            }
        }
    }

    private function rebuildTextFilterGroup(): void
    {
        $this->migrateTextFiltersIfNeeded();

        // Ensure at least one group exists
        if (empty($this->userFilters) || ! is_array($this->userFilters)) {
            $this->userFilters = [[]];
        }

        // Remove all existing text-source filters from all groups
        foreach ($this->userFilters as $groupIndex => $group) {
            if (! is_array($group)) {
                continue;
            }

            $this->userFilters[$groupIndex] = array_values(
                array_filter($group, fn ($f) => ($f['source'] ?? '') !== 'text')
            );
        }

        // Add text filters into their respective groups
        foreach ($this->textFilters as $groupIndex => $filtersInGroup) {
            if (! is_array($filtersInGroup)) {
                continue;
            }

            if (! isset($this->userFilters[$groupIndex])) {
                $this->userFilters[$groupIndex] = [];
            }

            foreach ($filtersInGroup as $col => $rawValue) {
                if ($rawValue === null || $rawValue === '') {
                    continue;
                }

                $values = is_array($rawValue) ? $rawValue : [$rawValue];
                foreach ($values as $singleValue) {
                    if ($singleValue === null || $singleValue === '') {
                        continue;
                    }

                    $parsed = $this->parseTextFilterValue($singleValue, $col);
                    $parsed['source'] = 'text';
                    $this->userFilters[$groupIndex][] = $parsed;
                }
            }
        }

        // Clean up empty groups
        $this->userFilters = array_values(
            array_filter($this->userFilters, fn ($g) => is_array($g) && ! empty($g))
        );
    }
}
