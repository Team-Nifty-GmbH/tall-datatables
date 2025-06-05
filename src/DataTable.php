<?php

namespace TeamNiftyGmbH\DataTable;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use Laravel\Scout\Searchable;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Spatie\ModelInfo\Attributes\Attribute;
use TallStackUi\Traits\Interactions;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;
use TeamNiftyGmbH\DataTable\Helpers\SessionFilter;
use TeamNiftyGmbH\DataTable\Traits\DataTables\StoresSettings;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsAggregation;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsCache;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsExporting;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsRelations;
use TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsSelecting;
use function Livewire\store;

class DataTable extends Component
{
    use Interactions, StoresSettings, SupportsAggregation, SupportsCache, SupportsExporting, SupportsRelations,
        SupportsSelecting;

    public array $appends = [];

    /**
     * These are the columns that will be available to the user.
     */
    #[Locked]
    public array $availableCols = ['*'];

    #[Locked]
    public array $availableRelations = ['*'];

    public array $colLabels = [];

    public array $columnLabels = [];

    public array $data = [];

    public array $enabledCols = [];

    /**
     * The default filters for the table, these will be applied on every query.
     * e.g. ['is_active' => true]
     * This will only show active records, no matter what userFilters will be set.
     * See it as a globalScope.
     */
    #[Locked]
    public array $filters = [];

    /**
     * If some of your cols have available values this variable contains the lists.
     * e.g. ['status' => ['active', 'inactive']]
     */
    public array $filterValueLists = [];

    public array $formatters = [];

    /**
     * If set to false the table will have no head, so no captions for the cols.
     */
    #[Locked]
    public bool $hasHead = true;

    /**
     * If set to true the table will show no pagination but
     * load more rows as soon as the table footer comes into viewport.
     */
    public bool $hasInfiniteScroll = false;

    /**
     * If set to true the table will not redirect to the detail page.
     * The alpinejs data-table-row-clicked event will be dispatched anyway.
     */
    public bool $hasNoRedirect = false;

    /**
     * If set to false the table will have no sidebar.
     */
    #[Locked]
    public bool $hasSidebar = true;

    public bool $hasStickyCols = true;

    /**
     * If not empty the given text will be displayed as a headline above the table.
     */
    public string $headline = '';

    public bool $initialized = false;

    /**
     * If set to false the table will not be filterable.
     */
    #[Locked]
    public bool $isFilterable = true;

    /**
     * This is set automatically by the component if its null.
     * If $this->model uses the Scout Searchable trait, this will be set to true.
     * You can force enable or disable the search by setting this to true or false.
     */
    public ?bool $isSearchable = null;

    public ?int $loadedFilterId = null;

    #[Locked]
    public ?string $modelKeyName = null;

    #[Locked]
    public ?string $modelTable = null;

    public bool $orderAsc = true;

    public string $orderBy = '';

    public int|string $page = '1';

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

    protected $listeners = ['loadData'];

    protected string $model;

    protected bool $useWireNavigate = true;

    protected string $view = 'tall-datatables::livewire.data-table';

    public function mount(): void
    {
        $this->colLabels = $this->getColLabels();

        if (! $this->modelKeyName || ! $this->modelTable) {
            $model = app($this->getModel());
            $this->modelKeyName = $this->modelKeyName ?: $model->getKeyName();
            $this->modelTable = $this->modelTable ?: $model->getTable();
        }
    }

    public function render(): View|Factory|Application|null
    {
        return view($this->getView(), $this->getViewData());
    }

    /**
     * This ensures that the table will be rendered only once.
     */
    public function hydrate(): void
    {
        if (! $this->initialized) {
            return;
        }

        $this->skipRender();
    }

    protected function getTableActions(): array
    {
        return [];
    }

    #[Renderless]
    public function applyUserFilters(): void
    {
        $this->colLabels = $this->getColLabels();
        $this->loadedFilterId = null;

        $this->startSearch();
    }

    #[Renderless]
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

        return array_values(
            array_unique(
                array_merge(
                    $this->enabledCols,
                    $availableCols,
                    [$this->modelKeyName]
                )
            )
        );
    }

    #[Renderless]
    public function getColLabels(?array $cols = null): array
    {
        $colLabels = array_flip(
            $cols
                ?: array_merge(
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
            'formatters' => $this->getFormatters(),
            'leftAppend' => $this->getLeftAppends(),
            'rightAppend' => $this->getRightAppends(),
            'topAppend' => $this->getTopAppends(),
            'bottomAppend' => $this->getBottomAppends(),
            'searchRoute' => $this->getSearchRoute(),
            'operatorLabels' => $this->getOperatorLabels(),
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
    public function getOperatorLabels(): array
    {
        return [
            'like' => __('like'),
            'not like' => __('not like'),
            'is null' => __('is null'),
            'is not null' => __('is not null'),
            'between' => __('between'),
            'and' => __('and'),
            'Now' => __('Now'),
            'minutes' => __('Minutes'),
            'hours' => __('Hours'),
            'days' => __('Days'),
            'weeks' => __('Weeks'),
            'months' => __('Months'),
            'years' => __('Years'),
            'minute' => __('Minute'),
            'hour' => __('Hour'),
            'day' => __('Day'),
            'week' => __('Week'),
            'month' => __('Month'),
            'year' => __('Year'),
            'sum' => __('Sum'),
            'avg' => __('Average'),
            'min' => __('Minimum'),
            'max' => __('Maximum'),
            'Start of' => __('Start of'),
            'End of' => __('End of'),
        ];
    }

    #[Renderless]
    public function gotoPage(int $page): void
    {
        $this->page = $page;
        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function loadData(): void
    {
        $this->initialized = true;

        $query = $this->buildSearch();
        $baseQuery = $query->clone();

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
            $this->data['aggregates'] = ! $this->search ? $aggregates : [];
        }

        if ($this->data['links'] ?? false) {
            array_pop($this->data['links']);
            array_shift($this->data['links']);
        }
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
        $this->perPage += $this->perPage;

        $this->loadData();
    }

    public function placeholder(): View|Factory|Application
    {
        return view('tall-datatables::livewire.placeholder');
    }

    #[Renderless]
    public function setPerPage(int $perPage): void
    {
        if ($this->page > $this->data['total'] / $perPage) {
            $this->page = (int) ceil($this->data['total'] / $perPage);
        }

        $this->perPage = $perPage;

        $this->cacheState();

        $this->loadData();
    }

    #[Renderless]
    public function sortTable(string $col): void
    {
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
        $this->page = '1';

        $this->cacheState();
        $this->loadData();
    }

    #[Renderless]
    public function updatedUserFilters(): void
    {
        $this->applyUserFilters();
    }

    protected function addFilter(Builder $query, string|int $type, array $filter): void
    {
        if (in_array($filter['column'] ?? false, $this->aggregatableRelationCols)) {
            $this->aggregatableRelationCols[$filter['column']] = $filter;

            return;
        }

        $filter = $this->parseFilter($filter);

        if (! is_string($type)) {
            $filter = array_is_list($filter) ? [$filter] : $filter;
            $target = explode('.', $filter['column']);

            $column = array_pop($target);
            $relation = implode('.', $target);
            if ($relation) {
                $filter['column'] = $column;
                $filter['relation'] = Str::camel($relation);

                if ($filter['value'] === '%*%') {
                    $this->whereHas($query, $filter['relation']);
                } elseif ($filter['value'] === '%!*%') {
                    $this->whereDoesntHave($query, $filter['relation']);
                } else {
                    $query->whereHas($filter['relation'], function (Builder $subQuery) use ($type, $filter) {
                        unset($filter['relation']);
                        $this->addFilter($subQuery, $type, $filter);

                        return $subQuery;
                    });
                }
            } elseif (in_array($filter['operator'], ['is null', 'is not null'])) {
                $this->whereNull($query, $filter);
            } elseif ($filter['operator'] === 'between') {
                $query->whereBetween($filter['column'], $filter['value']);
            } else {
                $query->where([array_values(
                    array_filter($filter, fn ($value) => $value == 0 || ! empty($value))
                )]);
            }

            return;
        }

        if (method_exists($this, $type)) {
            $this->{$type}($query, $filter);
        }
    }

    protected function allowSoftDeletes(): bool
    {
        return in_array(
            SoftDeletes::class,
            class_uses_recursive(
                $this->getModel()
            )
        );
    }

    protected function applyFilters(Builder $builder): Builder
    {
        // add fixed filters
        foreach ($this->filters as $type => $filter) {
            if (! is_string($type)) {
                if (($filter['operator'] ?? false) && in_array($filter['operator'], ['is null', 'is not null'])) {
                    $builder->whereNull(columns: $filter['column'], not: $filter['operator'] === 'is not null');
                } else {
                    $builder->where([array_values($filter)]);
                }

                continue;
            }

            if (method_exists($this, $type)) {
                $this->{$type}($builder, $filter);
            }
        }

        // add user filters
        $builder->where(function ($query): void {
            foreach ($this->userFilters as $index => $orFilter) {
                $query->where(function (Builder $query) use ($orFilter): void {
                    foreach ($orFilter as $type => $filter) {
                        $this->addFilter($query, $type, $filter);
                        $query->havingRaw('stock_postings_sum_posting > ?', [0]);
                    }
                }, boolean: $index > 0 ? 'or' : 'and');
            }
        });

        // add aggregatable relations
        foreach ($this->getAggregatableRelationCols() as $aggregatableRelationCol) {
            $builder->withAggregate(
                $aggregatableRelationCol->relation,
                $aggregatableRelationCol->column,
                $aggregatableRelationCol->function
            );
        }

        foreach ($this->aggregatableRelationCols as $index => $aggregatableRelationCol) {
            if (is_int($index)) {
                continue;
            }

            $filter = $this->parseFilter($aggregatableRelationCol);

            $builder->having($filter['column'], $filter['operator'], $filter['value']);
        }

        return $builder;
    }

    protected function buildSearch(): Builder
    {
        /** @var Model $model */
        $model = $this->getModel();

        foreach ($this->getAggregatableRelationCols() as $aggregatableRelationCol) {
            $this->aggregatableRelationCols[] = $aggregatableRelationCol->alias;
        }

        if ($this->search && method_exists($model, 'search')) {
            $query = $this->getScoutSearch()->toEloquentBuilder($this->enabledCols, $this->perPage, $this->page);
        } else {
            $query = $model::query();
        }

        if ($this->withSoftDeletes && $this->allowSoftDeletes()) {
            $query->withTrashed();
        }

        if ($this->userOrderBy) {
            $orderBy = $this->userOrderBy;
            $orderAsc = $this->userOrderAsc;
        } else {
            $orderBy = $this->orderBy;
            $orderAsc = $this->orderAsc;
        }

        if (Str::contains($orderBy, '.')) {
            $orderByColumn = Str::afterLast($orderBy, '.');
            $table = $this->addDynamicJoin($query, Str::beforeLast($orderBy, '.'));
            $orderBy = $table . '.' . $orderByColumn;

            $query->orderBy($orderBy, $orderAsc ? 'ASC' : 'DESC');
        } else {
            if ($orderBy) {
                $query->orderBy($orderBy, $orderAsc ? 'ASC' : 'DESC');
            } else {
                $query->orderBy($this->modelKeyName, 'DESC');
            }
        }

        // include selected relationships
        [
            $with,
            $select,
            $filterable,
            $this->filterValueLists,
            $this->sortable,
            $formatters,
            $enabledCols,
        ] = $this->constructWith();

        $this->enabledCols = $enabledCols;
        $this->formatters = array_merge($formatters, $this->formatters);

        $query->with($with);
        $query->select(array_merge($select, [$this->modelTable . '.' . $this->modelKeyName]));

        $query = $this->getBuilder($query);

        if (session()->has($this->getCacheKey() . '_query')) {
            $sessionFilter = session()->get($this->getCacheKey() . '_query');

            if ($sessionFilter instanceof SessionFilter) {
                $sessionFilter->getClosure()($query, $this);

                $this->sessionFilter = [
                    'name' => $sessionFilter->name,
                ];

                if (! $sessionFilter->loaded) {
                    $this->userFilters = [];
                    $sessionFilter->loaded = true;

                    session()->put($this->getCacheKey() . '_query', $sessionFilter);
                }
            }
        }

        return $this->applyFilters($query);
    }

    protected function compileActions(string $type): array
    {
        $actions = [];
        $methodBaseName = 'get' . ucfirst($type) . 'Actions';

        foreach (class_uses_recursive(static::class) as $trait) {
            $method = $methodBaseName . class_basename($trait);

            if (method_exists($this, $method)) {
                $actions = array_merge(
                    $this->$method(),
                    $actions
                );
            }
        }

        if (method_exists($this, $methodBaseName)) {
            $actions = array_merge(
                $this->{$methodBaseName}(),
                $actions
            );
        }

        return $actions;
    }

    /**
     * When you need to re-render the table you can call this to force rendering.
     */
    protected function forceRender(): void
    {
        store($this)->set('skipRender', false);
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
            $loadedColumns[$enabledCol] = [
                'column' => $attribute,
                'loaded_as' => $enabledCol,
            ];
            $loadedRelations[$path] = [
                'model' => $relation?->related ?? $this->getModel(),
                'loaded_columns' => $loadedColumns,
                'type' => $relation?->type,
            ];
        }

        return $loadedRelations;
    }

    protected function getIsSearchable(): bool
    {
        return is_null($this->isSearchable)
            ? in_array(Searchable::class, class_uses_recursive($this->getModel()))
            : $this->isSearchable;
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

    protected function getPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        return $paginator;
    }

    protected function getResultFromQuery(Builder $query): LengthAwarePaginator|Collection|array
    {
        try {
            if (property_exists($query, 'scout_pagination')) {
                $items = $query->get();
                $total = max(data_get($query->scout_pagination, 'estimatedTotalHits'), $query->count());
                $limit = data_get($query->scout_pagination, 'limit');
                $result = new LengthAwarePaginator(
                    $items,
                    $total,
                    $limit,
                    ceil(data_get($query->scout_pagination, 'offset') / $limit) + 1,
                );
            } else {
                $result = $query->paginate(
                    perPage: $this->perPage,
                    page: (int) $this->page
                );
            }
        } catch (QueryException $e) {
            $this->toast()->error($e->getMessage());

            return [];
        }
        $result = $this->getPaginator($result);
        $resultCollection = $result->getCollection();

        if (property_exists($query, 'hits')) {
            $mapped = $resultCollection->map(
                function (Model $item) use ($query) {
                    $itemArray = $this->itemToArray($item);

                    foreach ($itemArray as $key => $value) {
                        $itemArray[$key] = data_get($query->hits, $item->getKey() . '._formatted.' . $key, $value);
                    }

                    return $itemArray;
                }
            );
        } else {
            // only return the columns that are available
            $mapped = $resultCollection->map(
                function (Model $item) {
                    return $this->itemToArray($item);
                }
            );
        }

        $result->setCollection($mapped);

        return $result;
    }

    protected function getReturnKeys(): array
    {
        return array_filter(array_merge(
            $this->enabledCols,
            [$this->modelKeyName, 'href'],
            $this->withSoftDeletes ? ['deleted_at'] : []
        ));
    }

    protected function getRightAppends(): array
    {
        return [];
    }

    protected function getRowAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getScoutSearch(): \Laravel\Scout\Builder
    {
        return $this->getModel()::search($this->search);
    }

    protected function getTableFields(): \Illuminate\Support\Collection
    {
        return ModelInfo::forModel($this->getModel())
            ->attributes
            ->filter(function (Attribute $attribute) {
                return ! $attribute->virtual
                    && ! $attribute->appended;
            })
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
        ];
    }

    protected function itemToArray($item): array
    {
        if ($appends = $this->getAppends()) {
            $item->append($appends);
        }

        $rawArray = $item->toArray();
        $dotted = Arr::dot($rawArray);
        $itemArray = [];
        $returnKeys = $this->getReturnKeys();

        // n:n or 1:n or n:1 relations have numeric keys while the relation path has not
        // so we need to filter out the numeric keys and convert them to the relation path
        foreach ($dotted as $key => $value) {
            $originalKey = $key;
            $explodedKey = explode('.', $key);
            $key = array_filter($explodedKey, fn ($part) => ! is_numeric($part));
            $key = implode('.', $key);

            $shortenedKey = Str::beforeLast($key, '.');
            if (is_array(data_get($rawArray, Str::beforeLast($originalKey, '.'))) && in_array($shortenedKey, $returnKeys)) {
                $key = $shortenedKey;
            }

            if (! in_array($key, $returnKeys)) {
                continue;
            }

            if (array_key_exists($key, $itemArray)) {
                if (! is_array($itemArray[$key])) {
                    $itemArray[$key] = [$itemArray[$key]];
                }
                $itemArray[$key][] = $value;
            } else {
                $itemArray[$key] = $value;
            }
        }

        $itemArray['href'] = in_array(InteractsWithDataTables::class, class_implements($this->getModel()))
        && ! $this->hasNoRedirect
        && method_exists($item, 'getUrl')
            ? $item->getUrl()
            : null;

        return $itemArray;
    }

    protected function parseFilter(array $filter): array
    {
        $filter = Arr::only($filter, ['column', 'operator', 'value', 'relation']);
        $filter['value'] = is_array($filter['value']) ? $filter['value'] : [$filter['value']];

        $filter['value'] = collect($filter['value'])
            ->map(function ($value) use (&$filter) {
                if (
                    is_string($value)
                    && ! is_numeric($value)
                    && (
                        is_string($this->formatters[$filter['column']] ?? false)
                        && str_starts_with($this->formatters[$filter['column']], 'date')
                    )
                ) {
                    try {
                        return Carbon::parse($value)->toIso8601String();
                    } catch (InvalidFormatException) {
                        $filter['operator'] = 'like';

                        return '%' . $value . '%';
                    }
                }

                return is_numeric($value) ? (float) $value : $value;
            })
            ->map(function ($value) {
                if (! ($value['calculation'] ?? false)) {
                    return $value;
                }

                $functionPrefix = $value['calculation']['operator'] === '-' ? 'sub' : 'add';
                $functionSuffix = ucfirst($value['calculation']['unit']);

                if (
                    array_key_exists('is_start_of', $value['calculation'])
                    && is_numeric($value['calculation']['is_start_of'])
                ) {
                    $functionStartOfPrefix = $value['calculation']['is_start_of'] ? 'startOf' : 'endOf';
                    $functionStartOfSuffix = ucfirst($value['calculation']['start_of']);

                    return [
                        now()
                            ->{$functionPrefix . $functionSuffix}($value['calculation']['value'])
                            ->{$functionStartOfPrefix . $functionStartOfSuffix}(),
                    ];
                } else {
                    return [
                        now()
                            ->{$functionPrefix . $functionSuffix}($value['calculation']['value']),
                    ];
                }
            })
            ->all();

        $filter['value'] = count($filter['value']) === 1
            ? $filter['value'][0]
            : $filter['value'];

        return $filter;
    }

    protected function setData(array $data): void
    {
        $this->data = $data;
    }

    protected function showRestoreButton(): bool
    {
        return method_exists(static::class, 'restore');
    }

    /**
     * You should set the name of the route in your .env file.
     * e.g. TALL_DATATABLES_SEARCH_ROUTE=datatables.search
     * The route should lead to the SearchController from this package.
     */
    private function getSearchRoute(): string
    {
        return config('tall-datatables.search_route')
            ? route(config('tall-datatables.search_route'), '')
            : '';
    }

    private function where(Builder $builder, array $filter): Builder
    {
        return $builder->where($filter);
    }

    private function whereDoesntHave(Builder $builder, string $relation): Builder
    {
        return $builder->whereDoesntHave(Str::camel($relation));
    }

    private function whereHas(Builder $builder, string $relation): Builder
    {
        return $builder->whereHas(Str::camel($relation));
    }

    private function whereIn(Builder $builder, array $filter): Builder
    {
        return $builder->whereIn($filter[0], $filter[1]);
    }

    private function whereNull(Builder $builder, array $filter): Builder
    {
        return $builder->whereNull(
            columns: $filter['column'],
            boolean: ($filter['boolean'] ?? 'and') !== 'or' ? 'and' : 'or',
            not: $filter['operator'] === 'is not null'
        );
    }

    private function with(Builder $builder, array $filter): Builder
    {
        return $builder->with($filter);
    }
}
