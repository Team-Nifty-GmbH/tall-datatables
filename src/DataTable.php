<?php

namespace TeamNiftyGmbH\DataTable;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Laravel\Scout\Searchable;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use ReflectionClass;
use Spatie\ModelInfo\Attributes\Attribute;
use Spatie\ModelInfo\Relations\Relation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Exceptions\MissingTraitException;
use TeamNiftyGmbH\DataTable\Exports\DataTableExport;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;
use TeamNiftyGmbH\DataTable\Traits\HasDatatableUserSettings;
use WireUi\Traits\Actions;

use function Livewire\store;

class DataTable extends Component
{
    use Actions;

    public bool $initialized = false;

    public array $loadedModels = [];

    #[Locked]
    public ?string $modelKeyName = null;

    #[Locked]
    public ?string $modelTable = null;

    #[Locked]
    public ?string $cacheKey = null;

    /**
     * The default filters for the table, these will be applied on every query.
     * e.g. ['is_active' => true]
     * This will only show active records, no matter what userFilters will be set.
     * See it as a globalScope.
     */
    #[Locked]
    public array $filters = [];

    /**
     * These are the columns that will be available to the user.
     */
    #[Locked]
    public array $availableCols = ['*'];

    #[Locked]
    public array $availableRelations = ['*'];

    public array $enabledCols = [];

    public array $columnLabels = [];

    public array $userFilters = [];

    public array $savedFilters = [];

    public bool $showSavedFilters = true;

    public ?int $loadedFilterId = null;

    public array $exportColumns = [];

    public array $aggregatableCols = [
        'sum' => [],
        'avg' => [],
        'min' => [],
        'max' => [],
    ];

    public array $colLabels = [];

    /**
     * This is set automatically by the component if its null.
     * If $this->model uses the Scout Searchable trait, this will be set to true.
     * You can force enable or disable the search by setting this to true or false.
     */
    public ?bool $isSearchable = null;

    /**
     * If set to false the table will not show the export tab in the sidebar.
     */
    #[Locked]
    public bool $isExportable = true;

    /**
     * If set to false the table will not be filterable.
     */
    #[Locked]
    public bool $isFilterable = true;

    public bool $showFilterInputs = true;

    /**
     * If set to false the table will have no head, so no captions for the cols.
     */
    #[Locked]
    public bool $hasHead = true;

    /**
     * If set to false the table will have no sidebar.
     */
    #[Locked]
    public bool $hasSidebar = true;

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
     * If not empty the given text will be displayed as a headline above the table.
     */
    public string $headline = '';

    public string $search = '';

    public string $orderBy = '';

    public array $stickyCols = [];

    public bool $hasStickyCols = true;

    public bool $orderAsc = true;

    public string $userOrderBy = '';

    public bool $userOrderAsc = true;

    public int|string $page = '1';

    public int $perPage = 15;

    public array $sortable = ['*'];

    public array $aggregatable = ['*'];

    /**
     * If set to true the table rows will be selectable.
     */
    #[Locked]
    public bool $isSelectable = false;

    /**
     * Contains the selected ids of the table rows.
     */
    public array $selected = [];

    public array $selectedIndex = [];

    /**
     * If some of your cols have available values this variable contains the lists.
     * e.g. ['status' => ['active', 'inactive']]
     */
    public array $filterValueLists = [];

    public array $formatters = [];

    public array $appends = [];

    public array $data = [];

    protected string $model;

    protected string $view = 'tall-datatables::livewire.data-table';

    protected bool $useWireNavigate = true;

    protected $listeners = ['loadData'];

    private array $aggregatableRelationCols = [];

    public function hydrate(): void
    {
        if (! $this->initialized) {
            return;
        }

        $this->skipRender();
    }

    protected function forceRender(): void
    {
        store($this)->set('skipRender', false);
    }

    #[Renderless]
    public function getConfig(): array
    {
        return [
            'enabledCols' => $this->getEnabledCols(),
            'availableCols' => $this->getAvailableCols(),
            'colLabels' => $this->getColLabels(),
            'selectable' => $this->isSelectable,
            'sortable' => $this->getSortable(),
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

    protected function getEnabledCols(): array
    {
        return $this->enabledCols;
    }

    #[Renderless]
    public function getAvailableCols(): array
    {
        $availableCols = $this->availableCols === ['*']
            ? ModelInfo::forModel($this->model)->attributes->pluck('name')->toArray()
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
        array_walk($colLabels, function (&$value, $key) {
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

    protected function getAggregatable(): array
    {
        $foreignKeys = collect(Schema::getForeignKeys($this->modelTable))
            ->pluck('columns')
            ->flatten()
            ->unique()
            ->toArray();

        return $this->aggregatable === ['*']
            ? $this->getTableFields()
                ->filter(function (Attribute $attribute) use ($foreignKeys) {
                    return (in_array($attribute->phpType, ['int', 'float'])
                        || Str::contains($attribute->type, ['decimal', 'float', 'double', 'bigint']))
                        && ! in_array($attribute->name, $foreignKeys)
                        && ! $attribute->virtual
                        && ! $attribute->appended
                        && ! $attribute->hidden
                        && $attribute->name !== $this->modelKeyName;
                })
                ->pluck('name')
                ->toArray()
            : $this->aggregatable;
    }

    protected function getTableFields(): \Illuminate\Support\Collection
    {
        return ModelInfo::forModel($this->model)
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

    private function whereIn(Builder $builder, array $filter): Builder
    {
        return $builder->whereIn($filter[0], $filter[1]);
    }

    #[Renderless]
    public function getSortable(): array
    {
        $sortable = $this->sortable;
        if ($this->sortable === ['*']) {
            foreach ($this->getIncludedRelations() as $loadedRelation) {

                if (
                    $loadedRelation['type']
                    && ($loadedRelation['type'] !== BelongsTo::class || $loadedRelation['type'] !== HasOne::class)
                ) {
                    continue;
                }

                $columns = collect($loadedRelation['loaded_columns']);
                $attributes = ModelInfo::forModel($loadedRelation['model'])
                    ->attributes
                    ->filter(fn ($attribute) => (! $attribute->virtual) && $columns->contains('column', $attribute->name))
                    ->map(function (Attribute $attribute) use ($columns) {
                        return $columns->where('column', $attribute->name)?->value('loaded_as');
                    })
                    ->filter()
                    ->toArray();
                $sortable = array_merge($sortable, $attributes);
            }
        }

        return $sortable;
    }

    protected function getIncludedRelations(): array
    {
        $baseModelInfo = ModelInfo::forModel($this->model);
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
                'model' => $relation?->related ?? $this->model,
                'loaded_columns' => $loadedColumns,
                'type' => $relation?->type,
            ];
        }

        return $loadedRelations;
    }

    private function where(Builder $builder, array $filter): Builder
    {
        return $builder->where($filter);
    }

    #[Renderless]
    public function getFormatters(): array
    {
        $formatters = [];
        foreach ($this->getIncludedRelations() as $loadedRelation) {
            $relationFormatters = method_exists($loadedRelation['model'], 'typeScriptAttributes')
                ? $loadedRelation['model']::typeScriptAttributes()
                : (new $loadedRelation['model'])->getCasts();

            foreach ($loadedRelation['loaded_columns'] as $loadedColumn) {
                $formatters[$loadedColumn['loaded_as']] = $relationFormatters[$loadedColumn['column']] ?? null;
            }
        }

        return array_filter(array_merge($formatters, $this->formatters));
    }

    protected function getLeftAppends(): array
    {
        return [];
    }

    protected function getRightAppends(): array
    {
        return [];
    }

    protected function getTopAppends(): array
    {
        return [];
    }

    protected function getBottomAppends(): array
    {
        return [];
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
    public function updatedUserFilters(): void
    {
        $this->applyUserFilters();
    }

    #[Renderless]
    public function applyUserFilters(): void
    {
        $this->colLabels = $this->getColLabels();
        $this->loadedFilterId = null;

        $this->startSearch();
    }

    #[Renderless]
    public function startSearch(): void
    {
        $this->page = '1';

        $this->cacheState();
        $this->loadData();
    }

    private function cacheState(): void
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
                [
                    'name' => 'layout',
                    'cache_key' => $this->getCacheKey(),
                    'component' => get_class($this),
                    'settings' => [
                        'userFilters' => [],
                        'enabledCols' => $this->enabledCols,
                        'aggregatableCols' => $this->aggregatableCols,
                        'perPage' => $this->perPage,
                    ],
                    'is_layout' => true,
                ]
            );
        } catch (MissingTraitException) {
        }
    }

    #[Renderless]
    public function getCacheKey(): string
    {
        return $this->cacheKey ?: get_called_class();
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

    #[Renderless]
    public function loadData(): void
    {
        $this->initialized = true;

        $query = $this->buildSearch();
        $baseQuery = $query->clone();

        $result = $this->getResultFromQuery($query);

        $this->setData(is_array($result) ? $result : $result->toArray());

        if ($aggregates = $this->getAggregate($baseQuery)) {
            $this->data['aggregates'] = ! $this->search ? $aggregates : [];
        }

        if ($this->data['links'] ?? false) {
            array_pop($this->data['links']);
            array_shift($this->data['links']);
        }
    }

    protected function buildSearch(): Builder
    {
        /** @var Model $model */
        $model = $this->model;

        foreach ($this->getAggregatableRelationCols() as $aggregatableRelationCol) {
            $this->aggregatableRelationCols[] = $aggregatableRelationCol->alias;
        }

        if ($this->search && method_exists($model, 'search')) {
            $query = $this->getScoutSearch()
                ->toEloquentBuilder($this->enabledCols, $this->perPage, $this->page);
        } else {
            $query = $model::query();
        }

        if ($this->userOrderBy) {
            $orderBy = $this->userOrderBy;
            $orderAsc = $this->userOrderAsc;
        } else {
            $orderBy = $this->orderBy;
            $orderAsc = $this->orderAsc;
        }

        if (Str::contains($orderBy, '.')) {
            $relationPath = explode('.', $orderBy);
            $table = $relationPath[0];
            $orderByColumn = array_pop($relationPath);
            $localModel = new $model;
            $query->addSelect($localModel->getTable() . '.*');

            foreach ($relationPath as $key => $relation) {
                $class = new ReflectionClass($localModel);
                /** @var \Illuminate\Database\Eloquent\Relations\Relation $relationInstance */
                $relationInstance = $class->getMethod(Str::camel($relation))->invoke($localModel);

                if (! $relationInstance instanceof BelongsTo && ! $relationInstance instanceof HasOne) {
                    throw new InvalidArgumentException(
                        'Only belongsTo and hasOne relations are supported for sorting.'
                    );
                }

                if ($key === count($relationPath) - 1) {
                    $select = $relationInstance->getRelated()->getTable() . '.' . $orderByColumn;
                    $query->addSelect($select);
                }

                $table = $relationInstance->getRelated()->getTable();
                $first = $relationInstance instanceof BelongsTo ? $relationInstance->getQualifiedOwnerKeyName() : $relationInstance->getQualifiedParentKeyName();
                $second = $relationInstance->getQualifiedForeignKeyName();

                $query->join($table, $first, '=', $second)->where($second, '!=', null);

                $localModel = $relationInstance->getRelated();
            }

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
        $tmpWith = [];
        $with = [];
        $loadedModels = [];
        $baseModelInfo = ModelInfo::forModel($this->model);
        foreach ($this->enabledCols as $enabledCol) {
            if (str_contains($enabledCol, '.')) {
                $explodedCol = explode('.', $enabledCol);
                $attribute = array_pop($explodedCol);
                $path = implode('.', array_map(fn ($relation) => Str::camel($relation), $explodedCol));
                $relation = $baseModelInfo->relation(Str::camel($path));

                $explodedPath = explode('.', $path);
                $startModelInfo = $baseModelInfo;
                $relationPathModelInfo = [];
                foreach ($explodedPath as $index => $relationItem) {
                    if ($index === count($explodedPath) - 1) {
                        $relation = $startModelInfo->relation(Str::camel($relationItem));
                        $relationPathModelInfo[] = $relation;

                        break;
                    }

                    $currentRelation = $startModelInfo->relation(Str::camel($relationItem));
                    if ($currentRelation) {
                        $startModelInfo = ModelInfo::forModel($currentRelation->related);
                        $relationPathModelInfo[] = $currentRelation;
                    }
                }

                if (! $relation) {
                    continue;
                }

                $relatedModel = $relation->related;
                $loadedModels[$path] = $relatedModel;
                $addPath = null;

                if (! ModelInfo::forModel($relatedModel)->attribute($attribute)?->virtual) {
                    $relationModelQuery = new $baseModelInfo->class;

                    foreach ($relationPathModelInfo as $index => $currentRelation) {
                        $foreignKeysForeign = [];
                        $foreignKeysOwner = [];

                        $relationInstance = $relationModelQuery->{$currentRelation->name}();
                        if (! method_exists($relationInstance, 'getOwnerKeyName')
                            && ! method_exists($relationInstance, 'getForeignKeyName')
                        ) {
                            $with[] = $path;

                            continue;
                        }

                        if (method_exists($relationInstance, 'getOwnerKeyName')) {
                            $foreignKeysOwner[] = $relationInstance->getOwnerKeyName();
                            $addPath = $path;
                        }

                        if (method_exists($relationInstance, 'getForeignKeyName')
                            && $relationInstance instanceof HasOneOrMany
                        ) {
                            $addPath = array_slice($explodedPath, 0, $index);
                            $addPath = implode('.', $addPath);
                            $foreignKeysForeign[] = $relationInstance->getForeignKeyName();
                        }

                        $tmpWith[$addPath ?: $path] = array_values(array_unique(
                            array_merge($foreignKeysOwner, $foreignKeysForeign, $tmpWith[$addPath ?? $path] ?? [])
                        ));

                        $relationModelQuery = new $currentRelation->related;
                        $addPath = null;
                    }

                    $tmpWith[$path][] = $attribute;
                } else {
                    $with[] = $path;
                }
            }
        }

        foreach ($tmpWith as $path => $attributes) {
            $with[] = $path . ':' . implode(',', $attributes);
            $loadedModels = array_unique($loadedModels);
        }

        $this->loadedModels = $loadedModels;

        $query->with($with);

        $query = $this->getBuilder($query);

        return $this->applyFilters($query);
    }

    protected function getAggregatableRelationCols(): array
    {
        return [];
    }

    protected function getScoutSearch(): \Laravel\Scout\Builder
    {
        return $this->model::search($this->search);
    }

    private function with(Builder $builder, array $filter): Builder
    {
        return $builder->with($filter);
    }

    protected function getBuilder(Builder $builder): Builder
    {
        return $builder;
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
        $builder->where(function ($query) {
            foreach ($this->userFilters as $index => $orFilter) {
                $query->where(function (Builder $query) use ($orFilter) {
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

    private function whereNull(Builder $builder, array $filter): Builder
    {
        return $builder->whereNull(
            columns: $filter['column'],
            boolean: ($filter['boolean'] ?? 'and') !== 'or' ? 'and' : 'or',
            not: $filter['operator'] === 'is not null'
        );
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

    protected function parseFilter(array $filter): array
    {
        $filter = Arr::only($filter, ['column', 'operator', 'value', 'relation']);
        $filter['value'] = is_array($filter['value']) ? $filter['value'] : [$filter['value']];

        array_walk_recursive($filter['value'], function (&$value) {
            if (is_string($value) && ! is_numeric($value)) {
                try {
                    $value = Carbon::parse($value)->toIso8601String();
                } catch (InvalidFormatException) {
                }
            } elseif (is_numeric($value)) {
                $value = (float) $value;
            }
        });

        $filter['value'] = array_map(function ($value) {
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
        }, $filter['value']);
        $filter['value'] = count($filter['value']) === 1
            ? $filter['value'][0]
            : $filter['value'];

        return $filter;
    }

    private function whereHas(Builder $builder, string $relation): Builder
    {
        return $builder->whereHas(Str::camel($relation));
    }

    protected function getResultFromQuery(Builder $query): LengthAwarePaginator|Collection|array
    {
        try {
            if (property_exists($query, 'scout_pagination')) {
                $total = data_get($query->scout_pagination, 'estimatedTotalHits');
                $limit = data_get($query->scout_pagination, 'limit');
                $result = new LengthAwarePaginator(
                    $query->get(),
                    $total,
                    $limit,
                    ceil(data_get($query->scout_pagination, 'offset') / $limit) + 1,
                );
            } else {
                $result = $query->fastPaginate(
                    perPage: $this->perPage,
                    page: (int) $this->page
                );
            }
        } catch (QueryException $e) {
            $this->notification()->error($e->getMessage());

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

    protected function getPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        return $paginator;
    }

    protected function itemToArray($item): array
    {
        $returnKeys = $this->getReturnKeys();

        if ($appends = $this->getAppends()) {
            $item->append($appends);
        }

        $itemArray = $item->toArray();
        $preserved = [];
        $noDataUuid = Str::uuid()->toString();
        foreach ($returnKeys as $key) {
            $value = data_get($itemArray, $key, $noDataUuid);

            if ($value === $noDataUuid && is_array(data_get($itemArray, Str::beforeLast($key, '.')))) {
                $value = data_get($itemArray, Str::beforeLast($key, '.'));
                $itemArray[$key] = Arr::pluck($value, Str::afterLast($key, '.'));
            }

            if ($value && is_array($value) && ! Arr::isAssoc($value)) {
                $preserved[$key] = Arr::pull($itemArray, $key);
            }
        }

        $itemArray = Arr::only(array_merge(Arr::dot($itemArray), $preserved), $returnKeys);
        $itemArray['href'] = in_array(InteractsWithDataTables::class, class_implements($this->model))
            && ! $this->hasNoRedirect
            && method_exists($item, 'getUrl')
                ? $item->getUrl()
                : null;

        return $itemArray;
    }

    protected function getReturnKeys(): array
    {
        return array_filter(array_merge(
            $this->enabledCols,
            [$this->modelKeyName, 'href']
        ));
    }

    protected function getAppends(): array
    {
        return $this->appends;
    }

    protected function setData(array $data): void
    {
        $this->data = $data;
    }

    protected function getAggregate(Builder $builder): array
    {
        $aggregates = [];
        foreach ($this->aggregatableCols as $type => $columns) {
            if (! in_array($type, ['sum', 'avg', 'min', 'max'])) {
                continue;
            }

            if (! is_array($columns)) {
                $columns = [$columns];
            }

            foreach ($columns as $column) {
                if (! in_array($column, $this->enabledCols)) {
                    continue;
                }

                try {
                    $aggregates[$type][$column] = $builder->{$type}($column);
                } catch (QueryException $e) {
                    $this->notification()->error($e->getMessage());

                    continue;
                }
            }
        }

        return $aggregates;
    }

    public function placeholder(): View|Factory|Application
    {
        return view('tall-datatables::livewire.placeholder');
    }

    public function mount(): void
    {
        if (config('tall-datatables.should_cache')) {
            $cachedFilters = Session::get(config('tall-datatables.cache_key') . '.filter:' . $this->getCacheKey());
        } else {
            $cachedFilters = null;
        }

        $loadFilter = $cachedFilters;
        $this->savedFilters = $this->getSavedFilters();

        // no cached filter but saved filters
        if (! $loadFilter && $this->savedFilters) {
            $loadFilter = data_get(
                collect($this->savedFilters)->where('is_permanent', true)->first(),
                'settings',
                []
            );
        }

        // no permanent filter but layout filter
        if (! $loadFilter && $this->savedFilters) {
            $loadFilter = data_get(
                collect($this->savedFilters)->where('is_layout', true)->first(),
                'settings',
                []
            );
        }

        $this->loadFilter($loadFilter ?? [], false);
        $this->colLabels = $this->getColLabels();

        if (! $this->modelKeyName || ! $this->modelTable) {
            $model = (new $this->model);
            $this->modelKeyName = $this->modelKeyName ?: $model->getKeyName();
            $this->modelTable = $this->modelTable ?: $model->getTable();
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

    #[Renderless]
    public function loadFilter(array $properties, bool $skipRender = true): void
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

    public function render(): View|Factory|Application|null
    {
        return view($this->view, $this->getViewData());
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
            'rowActions' => $this->getRowActions(),
            'tableActions' => $this->getTableActions(),
            'selectedActions' => $this->getSelectedActions(),
            'modelName' => Str::headline(class_basename($this->model)),
            'showFilterInputs' => $this->showFilterInputs,
            'layout' => $this->getLayout(),
            'useWireNavigate' => $this->useWireNavigate,
            'colLabels' => $this->colLabels,
        ];
    }

    protected function getIsSearchable(): bool
    {
        return is_null($this->isSearchable)
            ? in_array(Searchable::class, class_uses_recursive($this->model))
            : $this->isSearchable;
    }

    protected function getComponentAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getTableHeadColAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    #[Renderless]
    public function getSelectAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getRowAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getCellAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    protected function getRowActions(): array
    {
        return [];
    }

    protected function getTableActions(): array
    {
        return [];
    }

    protected function getSelectedActions(): array
    {
        return [];
    }

    protected function getLayout(): string
    {
        return 'tall-datatables::layouts.table';
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

    #[Renderless]
    public function applyAggregations(): void
    {
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
    public function goToPage(int $page): void
    {
        $this->page = $page;
        $this->cacheState();
        $this->loadData();
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
    public function loadMore(): void
    {
        $this->perPage += $this->perPage;

        $this->loadData();
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
    public function getFilterableColumns(?string $name = null): array
    {
        if (! $this->isFilterable) {
            return [];
        }

        if (! $name) {
            $models = array_merge($this->loadedModels, [$this->model]);
        } else {
            $models = [
                ModelInfo::forModel($this->model)
                    ->relations
                    ->filter(fn ($relation) => $relation->name === $name)
                    ->first()
                    ->related,
            ];
        }

        $tableCols = [];
        foreach ($models as $prefix => $modelClass) {
            $prefix = Str::snake($prefix);
            $this->filterValueLists = method_exists($modelClass, 'getStates')
                ? $modelClass::getStates()
                    ->map(function ($state) {
                        return $state->map(function ($value) {
                            return ['value' => $value, 'label' => __($value)];
                        });
                    })
                    ->toArray()
                : [];

            $attributes = ModelInfo::forModel($modelClass)->attributes->each(
                function (Attribute $attribute) {
                    if ($attribute->type === 'boolean') {
                        $this->filterValueLists[$attribute->name] = [
                            [
                                'value' => 1,
                                'label' => __('Yes'),
                            ],
                            [
                                'value' => 0,
                                'label' => __('No'),
                            ],
                        ];
                    }
                }
            );

            $currentTableCols = $attributes->filter(fn (Attribute $attribute) => ! $attribute->virtual)
                ->pluck('name')
                ->toArray();

            $currentTableCols = ! is_numeric($prefix)
                ? array_map(fn ($col) => $prefix . '.' . $col, $currentTableCols)
                : $currentTableCols;
            $tableCols = array_merge($tableCols, $currentTableCols);
        }

        return array_values(array_intersect($this->enabledCols, $tableCols));
    }

    #[Renderless]
    public function getRelationTableCols(?string $relationName = null): array
    {
        $relationName = $relationName ? Str::camel($relationName) : null;
        $model = $relationName
            ? ModelInfo::forModel($this->model)
                ->relations
                ->filter(fn ($relation) => $relation->name === $relationName)
                ->first()
                ?->related
            : $this->model;

        if (! $model) {
            return [];
        }

        return ModelInfo::forModel($model)
            ->attributes
            ->filter(fn ($attribute) => ! $attribute->virtual)
            ->when(
                $this->availableCols !== ['*'],
                fn ($attributes) => $attributes->whereIn('name', $this->availableCols)
            )
            ->pluck('formatter', 'name')
            ->toArray();
    }

    #[Renderless]
    public function getRelationAttributes(string $relationName): array
    {
        if (! $relationName) {
            $model = $this->model;
        } else {
            $model = ModelInfo::forModel($this->model)
                ->relations
                ->filter(fn ($relation) => $relation->name === Str::camel($relationName))
                ->first()
                ?->related;
        }

        return array_map(
            fn ($attribute) => [
                'value' => ($relationName ? Str::snake($relationName) . '.' : '') . $attribute,
                'label' => __(Str::headline($attribute)),
            ],
            $this->getModelAttributes($model)
        );
    }

    private function getModelAttributes(string $modelClass): array
    {
        return ModelInfo::forModel($modelClass)
            ->attributes
            ->when(
                $this->availableCols !== ['*'],
                fn ($attributes) => $attributes->whereIn('name', $this->availableCols)
            )
            ->pluck('name')
            ->toArray();
    }

    #[Renderless]
    public function loadRelations(?string $model): array
    {
        $basePath = __(Str::of(class_basename($this->model))->headline()->toString());
        if ($model) {
            $basis = $basePath . ' -> ' . __(Str::headline(class_basename($model)));
        } else {
            $basis = $basePath;
        }

        if (! $model) {
            $model = $this->model;
        }

        return array_values(ModelInfo::forModel($model)
            ->relations
            ->filter(function ($relation) {
                return in_array($relation->name, $this->availableRelations) || $this->availableRelations === ['*'];
            })
            ->map(function (Relation $relation) use ($basis) {
                return [
                    'value' => Str::snake($relation->name),
                    'label' => $basis . ' -> ' . __(Str::headline($relation->name)),
                ];
            })
            ->toArray());
    }

    #[Renderless]
    public function getExportableColumns(): array
    {
        return array_unique(array_merge($this->availableCols, $this->enabledCols));
    }

    #[Renderless]
    public function export(array $columns = []): Response|BinaryFileResponse
    {
        $query = $this->buildSearch();

        return (new DataTableExport($query, array_filter($columns)))
            ->download(class_basename($this->model) . '_' . now()->toDateTimeLocalString('minute') . '.xlsx');
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
            Session::remove(config('tall-datatables.cache_key') . '.filter:' . $this->getCacheKey());

            $layout?->delete();
            $this->reset('enabledCols', 'aggregatableCols', 'userFilters', 'userOrderBy', 'userOrderAsc');
            $this->loadData();
        } catch (MissingTraitException) {
        }
    }
}
