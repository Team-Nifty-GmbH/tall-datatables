<?php

namespace TeamNiftyGmbH\DataTable;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        if (empty($this->data)) {
            $this->loadData();
        }

        return view($this->getView(), $this->getViewData());
    }

    /**
     * Clear data before sending to client — it's only needed during render, not in the snapshot.
     * This drastically reduces payload size since data can contain hundreds of formatted rows.
     * On the next request, loadData() will re-populate it.
     */
    public function dehydrate(): void
    {
        $this->data = [];
    }

    protected function getTableActions(): array
    {
        return [];
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

    public function applyUserFilters(): void
    {
        $this->colLabels = $this->getColLabels();
        $this->loadedFilterId = null;
        $this->startSearch();
    }

    public function clearFiltersAndSort(): void
    {
        $this->userFilters = [];
        $this->userOrderBy = '';
        $this->userOrderAsc = true;
        $this->search = '';
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

    public function gotoPage(int $page): void
    {
        $this->page = $page;
        $this->cacheState();
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->initialized = true;

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

            return;
        }

        $result = $this->getResultFromQuery($query);

        if (collect($result)->isEmpty() && $this->page > 1) {
            $this->reset('page');
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
    }

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

    public function loadMore(): void
    {
        $this->perPage += $this->perPage;
        $this->loadData();
    }

    public function placeholder(): View|Factory|Application
    {
        return view('tall-datatables::livewire.placeholder');
    }

    public function reloadData(): void
    {
        $this->loadData();
    }

    public function setPerPage(int $perPage): void
    {
        if ($perPage > 0 && ($this->data['total'] ?? 0) > 0 && $this->page > $this->data['total'] / $perPage) {
            $this->page = (int) ceil($this->data['total'] / $perPage);
        }

        $this->perPage = $perPage;
        $this->cacheState();
        $this->loadData();
    }

    public function sortTable(string $col): void
    {
        if ($this->userOrderBy === $col) {
            $this->userOrderAsc = ! $this->userOrderAsc;
        }

        $this->userOrderBy = $col;
        $this->cacheState();
        $this->loadData();
    }

    public function startSearch(): void
    {
        $this->reset('selected');
        $this->page = 1;
        $this->cacheState();
        $this->loadData();
    }

    public function updatedSearch(): void
    {
        $this->startSearch();
    }

    public function updatedStickyCols(): void
    {
        $this->renderIsland('body');
    }

    public function updatedUserFilters(): void
    {
        if ($this->loadingFilter) {
            $this->loadingFilter = false;

            return;
        }

        $this->applyUserFilters();
    }

    public function getIslandData(): array
    {
        return $this->getViewData();
    }

    protected function compileActions(string $type): array
    {
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

        return $actions;
    }

    /**
     * Hook to add computed columns (e.g. avatar) before formatters are applied.
     * Override this instead of itemToArray() to ensure formatters work on your custom columns.
     */
    protected function augmentItemArray(array &$itemArray, Model $item): void {}

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
        return [
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

    protected function setData(array $data): void
    {
        $this->data = $data;
    }

    protected function showRestoreButton(): bool
    {
        return method_exists(static::class, 'restore');
    }
}
