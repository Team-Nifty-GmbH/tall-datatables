<?php

namespace TeamNiftyGmbH\DataTable;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use Laravel\Scout\Searchable;
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
use TeamNiftyGmbH\DataTable\Traits\WithLockedPublicPropertiesTrait;
use WireUi\Traits\Actions;

class DataTable extends Component
{
    use Actions, WithLockedPublicPropertiesTrait;

    public bool $initialized = false;

    protected string $model;

    /**
     * The default filters for the table, these will be applied on every query.
     * e.g. ['is_active' => true]
     * This will only show active records, no matter what userFilters will be set.
     * See it as a globalScope.
     *
     * @locked
     */
    public array $filters = [];

    /**
     * These are the the columns that will be available to the user.
     *
     * @locked
     */
    public array $availableCols = [];

    /** @locked  */
    public array $availableRelations = [];

    public array $enabledCols = [];

    public array $userFilters = [];

    public array $savedFilters = [];

    public array $exportColumns = [];

    public array $aggregatableCols = [
        'sum' => [],
        'avg' => [],
        'min' => [],
        'max' => [],
    ];

    /**
     * This is set automatically by the component if its null.
     * If $this->model uses the Scout Searchable trait, this will be set to true.
     * You can force enable or disable the search by setting this to true or false.
     */
    public ?bool $isSearchable = null;

    /**
     * If set to false the table will not show the export tab in the sidebar.
     *
     * @locked
     */
    public bool $isExportable = true;

    /**
     * If set to false the table will not be filterable.
     *
     * @locked
     */
    public bool $isFilterable = true;

    /**
     * If set to false the table will have no head, so no captions for the cols.
     *
     * @locked
     */
    public bool $hasHead = true;

    /**
     * if set to true the table will show no pagination but
     * load more rows as soon as the table footer comes into viewport.
     */
    public bool $hasInfiniteScroll = false;

    /**
     * If set to true the table will not redirect to the detail page.
     * The alpinejs data-table-row-clicked event will be dispatched anyway.
     */
    public bool $hasNoRedirect = false;

    public string $search = '';

    public string $orderBy = '';

    public bool $orderAsc = true;

    public string $page = '1';

    public int $perPage = 15;

    public array $colLabels = [];

    public array $stretchCol = [];

    public array $sortable = [];

    public array $aggregatable = [];

    /**
     * If set to true the table rows will be selectable.
     *
     * @locked
     */
    public bool $selectable = false;

    /**
     * Contains the selected ids of the table rows.
     */
    public array $selected = [];

    /**
     * If some of your cols have available values this variable contains the lists.
     * e.g. ['status' => ['active', 'inactive']]
     */
    public array $filterValueLists = [];

    public array $formatters = [];

    public array $appends = [];

    public array $data = [];

    protected $listeners = ['loadData'];

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'cols' => $this->enabledCols,
            'enabledCols' => $this->availableCols,
            'colLabels' => $this->colLabels,
            'selectable' => $this->selectable,
            'sortable' => $this->sortable,
            'aggregatable' => $this->aggregatable,
            'stretchCol' => $this->stretchCol,
            'formatters' => $this->formatters,
            'searchRoute' => $this->getSearchRoute(),
        ];
    }

    /**
     * @return ComponentAttributeBag
     */
    public function getRowAttributes(): ComponentAttributeBag
    {
        return new ComponentAttributeBag();
    }

    /**
     * @return array
     */
    public function getRowActions(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getTableActions(): array
    {
        return [];
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        if (config('tall-datatables.should_cache')) {
            $cachedFilters = Session::get(config('tall-datatables.cache_key') . '.filter:' . get_called_class());
        } else {
            $cachedFilters = null;
        }

        $this->loadFilter(
            $cachedFilters ?: data_get(
                collect($this->getSavedFilters())
                    ->where('is_permanent', true)
                    ->first(),
                'settings',
                []
            ),
            false
        );

        $this->availableCols = array_values(
            array_unique(array_merge($this->enabledCols, $this->availableCols, ['id']))
        );

        $tableFields = ModelInfo::forModel($this->model)
            ->attributes
            ->filter(function (Attribute $attribute) {
                return ! $attribute->virtual
                    && ! $attribute->appended
                    && in_array($attribute->name, $this->availableCols);
            });

        $this->colLabels = array_flip($this->availableCols);
        array_walk($this->colLabels, function (&$value, $key) {
            $value = __(Str::headline($key));
        });

        $this->sortable = $this->sortable === ['*']
            ? $tableFields->pluck('name')->toArray()
            : $this->sortable;

        $this->aggregatable = $this->aggregatable === ['*']
            ? $tableFields
                ->filter(function (Attribute $attribute) {
                    return in_array($attribute->phpType, ['int', 'float'])
                        || Str::contains($attribute->type, ['decimal', 'float', 'double']);
                })
                ->pluck('name')
                ->toArray()
            : $this->aggregatable;

        $this->isSearchable = is_null($this->isSearchable)
            ? in_array(Searchable::class, class_uses_recursive($this->model))
            : $this->isSearchable;

        $this->getFormatters();
    }

    /**
     * @return Application|Factory|View
     */
    public function render(): View|Factory|Application
    {
        return view('tall-datatables::livewire.data-table',
            [
                'rowAttributes' => $this->getRowAttributes(),
                'rowActions' => $this->getRowActions(),
                'tableActions' => $this->getTableActions(),
                'modelName' => class_basename($this->model),
            ]
        );
    }

    /**
     * @return void
     */
    public function updatedSearch(): void
    {
        $this->page = '1';

        $this->cacheState();
        $this->loadData();

        $this->skipRender();
    }

    /**
     * @return void
     */
    public function updatedUserFilters(): void
    {
        $this->skipRender();

        $this->updatedSearch();
    }

    /**
     * @return void
     */
    public function updatedaggregatableCols(): void
    {
        $this->skipRender();

        $this->cacheState();
        $this->loadData();
    }

    /**
     * @param $col
     * @return void
     */
    public function sortTable($col): void
    {
        $this->skipRender();

        if ($this->orderBy === $col) {
            $this->orderAsc = ! $this->orderAsc;
        }

        $this->orderBy = $col;

        $this->cacheState();
        $this->loadData();
    }

    /**
     * @param array $cols
     * @return void
     */
    public function storeColLayout(array $cols): void
    {
        $reload = count($cols) > count($this->enabledCols);

        $this->enabledCols = $cols;

        $this->cacheState();
        if ($reload) {
            $this->loadData();
        }

        $this->skipRender();
    }

    /**
     * @return void
     */
    public function updatedPage(): void
    {
        $this->skipRender();

        $this->cacheState();
        $this->loadData();
    }

    /**
     * @return void
     */
    public function updatedPerPage(): void
    {
        $this->skipRender();

        $this->cacheState();
        $this->loadData();
    }

    /**
     * @return void
     */
    public function getFormatters(): void
    {
        $this->formatters = method_exists($this->model, 'typeScriptAttributes')
            ? array_merge($this->model::typeScriptAttributes(), $this->formatters)
            : $this->formatters;
    }

    /**
     * @return void
     */
    public function loadMore()
    {
        $this->perPage += $this->perPage;

        $this->loadData();
    }

    /**
     * @return void
     */
    public function loadData(): void
    {
        $this->initialized = true;

        $baseQuery = $this->buildSearch();
        $query = $baseQuery->clone();

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

            return;
        }
        $result = $this->getPaginator($result);
        $resultCollection = $result->getCollection();

        $aggregates = [];
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

            $aggregates = $this->getAggregate($baseQuery);
        }

        $result->setCollection($mapped);

        $result = $result->toArray();
        $result['aggregates'] = $aggregates;
        $this->setData($result);

        array_pop($this->data['links']);
        array_shift($this->data['links']);
    }

    /**
     * @param Builder $builder
     * @return array
     */
    public function getAggregate(Builder $builder): array
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

    /**
     * @return array
     */
    public function getReturnKeys(): array
    {
        return array_merge(
            $this->enabledCols,
            [(new $this->model)->getKeyName()]
        );
    }

    /**
     * @param $item
     * @return array
     */
    public function itemToArray($item): array
    {
        $returnKeys = $this->getReturnKeys();

        if ($appends = $this->getAppends()) {
            $item->append($appends);
        }

        $itemArray = $item->toArray();
        $preserved = [];
        foreach ($returnKeys as $key) {
            $value = $itemArray[$key] ?? false;
            if ($value && is_array($value) && ! Arr::isAssoc($value)) {
                $preserved[$key] = Arr::pull($itemArray, $key);
            }
        }

        $itemArray = Arr::only(array_merge(Arr::dot($itemArray), $preserved), $returnKeys);
        $itemArray['href'] = in_array(InteractsWithDataTables::class, class_implements($this->model))
            && ! $this->hasNoRedirect
                ? $item->getUrl()
                : null;

        return $itemArray;
    }

    /**
     * @return array
     */
    public function getAppends(): array
    {
        return $this->appends;
    }

    /**
     * @param string $name
     * @param bool $permanent
     * @return void
     *
     * @throws MissingTraitException
     */
    public function saveFilter(string $name, bool $permanent = false): void
    {
        $this->ensureAuthHasTrait();

        if ($permanent) {
            Auth::user()->datatableUserSettings()->update(['is_permanent' => false]);
        }

        Auth::user()->datatableUserSettings()->create([
            'name' => $name,
            'component' => get_called_class(),
            'settings' => [
                'enabledCols' => $this->enabledCols,
                'aggregatableCols' => $this->aggregatableCols,
                'userFilters' => $this->userFilters,
                'orderBy' => $this->orderBy,
                'orderAsc' => $this->orderAsc,
                'perPage' => $this->perPage,
            ],
            'is_permanent' => $permanent,
        ]);

        $this->skipRender();
    }

    /**
     * @param string $id
     * @return void
     *
     * @throws MissingTraitException
     */
    public function deleteSavedFilter(string $id): void
    {
        $this->ensureAuthHasTrait();

        Auth::user()->datatableUserSettings()->whereKey($id)->delete();

        $this->skipRender();
    }

    /**
     * @param array $properties
     * @param bool $skipRender
     * @return void
     */
    public function loadFilter(array $properties, bool $skipRender = true): void
    {
        if (! $properties) {
            return;
        }

        foreach ($properties as $property => $value) {
            $this->{$property} = $value;
        }

        if ($skipRender) {
            $this->skipRender();
        }

        if ($this->initialized) {
            $this->loadData();
        }
    }

    /**
     * @param string|null $name
     * @return array
     */
    public function loadFields(?string $name = null): array
    {
        if (! $name) {
            $modelClass = $this->model;
        } else {
            $modelClass = ModelInfo::forModel($this->model)
                ->relations
                ->filter(fn ($relation) => $relation->name === $name)
                ->first()
                ->related;
        }

        $this->filterValueLists = method_exists($modelClass, 'getStates')
            ? $modelClass::getStates()
                ->map(function ($state) {
                    return $state->map(function ($value) {
                        return ['value' => $value, 'label' => __($value)];
                    }
                    );
                }
                )
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

        $this->skipRender();

        $tableCols = $attributes->filter(fn (Attribute $attribute) => ! $attribute->virtual)
            ->pluck('name')
            ->toArray();

        $this->skipRender();

        return array_values(array_intersect($this->availableCols, $tableCols));
    }

    /**
     * @return array
     */
    public function loadRelations(): array
    {
        $basis = __(class_basename($this->model));

        return array_values(ModelInfo::forModel($this->model)
            ->relations
            ->filter(function ($relation) {
                return in_array($relation->name, $this->availableRelations) || $this->availableRelations === ['*'];
            })
            ->map(function (Relation $relation) use ($basis) {
                return [
                    'value' => $relation->name,
                    'label' => $basis . ' -> ' . __(Str::headline($relation->name)),
                ];
            })
            ->toArray());
    }

    /**
     * @return array
     */
    public function getSavedFilters(): array
    {
        if (method_exists(Auth::user(), 'getDataTableSettings')) {
            return Auth::user()
                ->getDataTableSettings()
                ?->toArray() ?: [];
        } else {
            return [];
        }
    }

    /**
     * @return array
     */
    public function getExportColumns(): array
    {
        return $this->availableCols;
    }

    /**
     * @param array $columns
     * @return Response|BinaryFileResponse
     */
    public function export(array $columns = []): Response|BinaryFileResponse
    {
        $query = $this->buildSearch();

        return (new DataTableExport($query, array_keys(array_filter($columns, fn ($value) => $value))))
            ->download(class_basename($this->model) . '_' . now()->toDateTimeLocalString('minute') . '.xlsx');
    }

    /**
     * @return Builder
     */
    public function buildSearch(): Builder
    {
        /** @var Model $model */
        $model = $this->model;

        if ($this->search && method_exists($model, 'search')) {
            $query = $model::search($this->search)
                ->toEloquentBuilder($this->enabledCols, $this->perPage, $this->page);
        } else {
            $query = $model::query();
        }

        if (Str::contains($this->orderBy, '.')) {
            $relationPath = explode('.', $this->orderBy);
            $table = $relationPath[0];
            $orderByColumn = array_pop($relationPath);
            $localModel = new $model;
            $query->addSelect($localModel->getTable() . '.*');

            foreach ($relationPath as $key => $relation) {
                $class = new ReflectionClass($localModel);
                /** @var \Illuminate\Database\Eloquent\Relations\Relation $relationInstance */
                $relationInstance = $class->getMethod(Str::camel($relation))->invoke($localModel);

                if (! $relationInstance instanceof BelongsTo && ! $relationInstance instanceof HasOne) {
                    throw new \InvalidArgumentException(
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
            $query->orderBy($orderBy, $this->orderAsc ? 'ASC' : 'DESC');
        } else {
            if ($this->orderBy) {
                $query->orderBy($this->orderBy, $this->orderAsc ? 'DESC' : 'ASC');
            } else {
                $query->orderBy((new $model)->getKeyName(), 'DESC');
            }
        }

        $query = $this->getBuilder($query);

        return $this->applyFilters($query);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function getBuilder(Builder $builder): Builder
    {
        return $builder;
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @return LengthAwarePaginator
     */
    public function getPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        return $paginator;
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function applyFilters(Builder $builder): Builder
    {
        foreach ($this->filters as $type => $filter) {
            if (! is_string($type)) {
                $filter = array_is_list($filter) ? [$filter] : $filter;
                $builder->where($filter);

                continue;
            }

            if (method_exists($this, $type)) {
                $this->{$type}($builder, $filter);
            }
        }

        $builder->where(function ($query) {
            foreach ($this->userFilters as $index => $orFilter) {
                $query->where(function ($query) use ($orFilter) {
                    foreach ($orFilter as $type => $filter) {
                        if (! is_string($type)) {
                            $filter = array_is_list($filter) ? [$filter] : $filter;
                            $target = explode('.', $filter['column']);

                            $column = array_pop($target);
                            $relation = implode('.', $target);

                            if ($relation) {
                                $filter['column'] = $column;
                                $filter['relation'] = $relation;
                                $this->whereRelation($query, $filter);
                            } else {
                                $query->where([array_values($filter)]);
                            }

                            continue;
                        }

                        if (method_exists($this, $type)) {
                            $this->{$type}($query, $filter);
                        }
                    }
                }, boolean: $index > 0 ? 'or' : 'and');
            }
        });

        return $builder;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param Builder $builder
     * @param array $filter
     * @return Builder
     */
    private function whereIn(Builder $builder, array $filter): Builder
    {
        return $builder->whereIn($filter[0], $filter[1]);
    }

    /**
     * @param Builder $builder
     * @param array $filter
     * @return Builder
     */
    private function where(Builder $builder, array $filter): Builder
    {
        return $builder->where($filter[0], $filter[1], $filter[2]);
    }

    /**
     * @param Builder $builder
     * @param array $filter
     * @return Builder
     */
    private function with(Builder $builder, array $filter): Builder
    {
        return $builder->with($filter);
    }

    /**
     * @param Builder $builder
     * @param array $filter
     * @return Builder
     */
    private function whereRelation(Builder $builder, array $filter): Builder
    {
        return $builder->whereRelation($filter['relation'], $filter['column'], $filter['operator'], $filter['value']);
    }

    /**
     * @return void
     */
    private function cacheState(): void
    {
        $filter = [
            'userFilters' => $this->userFilters,
            'enabledCols' => $this->enabledCols,
            'aggregatableCols' => $this->aggregatableCols,
            'orderBy' => $this->orderBy,
            'orderAsc' => $this->orderAsc,
            'perPage' => $this->perPage,
            'page' => $this->page,
            'search' => $this->search,
            'selected' => $this->selected,
        ];

        if (config('tall-datatables.should_cache')) {
            Session::put(config('tall-datatables.cache_key') . '.filter:' . get_called_class(), $filter);
        }
    }

    /**
     * Resolves a foreign key to a relation.
     *
     * @param string $localKey
     * @param string|null $relation
     * @return string|array|null
     */
    public function resolveForeignKey(string $localKey, string $relation = null): string|array|null
    {
        $model = new ($relation ? ModelInfo::forModel($this->model)->relation($relation)->related : $this->model);

        if (! $localKey) {
            return null;
        }

        $schema = $model->getConnection()->getDoctrineSchemaManager();
        $table = $model->getConnection()->getTablePrefix() . $model->getTable();

        $result = collect($schema->introspectTable($table)
            ->getForeignKeys())
            ->map(function ($foreign) {
                return [
                    'local' => $foreign->getLocalColumns()[0] ?? '',
                    'foreign' => $foreign->getForeignColumns()[0] ?? '',
                    'foreign_table' => $foreign->getForeignTableName(),
                ];
            });

        $relatedTable = data_get($result->where('local', $localKey)->first(), 'foreign_table');

        if (! $relatedTable) {
            return null;
        }

        $relatedModel = ModelInfo::forAllModels()
            ->where('tableName', $relatedTable)
            ->first()
            ?->class;

        if (! $relatedModel) {
            return null;
        }

        if (in_array(Searchable::class, class_uses_recursive($relatedModel))) {
            return $relatedModel;
        }

        if ($relatedModel::count() > 100) {
            return null;
        }

        $hasLabel = false;
        if (in_array(InteractsWithDataTables::class, class_implements($relatedModel))) {
            $hasLabel = true;
        }

        return $relatedModel::all()->map(function (Model $item) use ($hasLabel) {
            return [
                'value' => $item->getKey(),
                'label' => $hasLabel ? $item->getLabel() : $item->getKey(),
                'description' => $hasLabel ? $item->getDescription() : null,
            ];
        })->toArray();
    }

    /**
     * You should set the name of the route in your .env file.
     * e.g. TALL_DATATABLES_SEARCH_ROUTE=datatables.search
     * The route should lead to the SearchController from this package.
     *
     * @return string
     */
    private function getSearchRoute(): string
    {
        return config('tall-datatables.search_route')
            ? route(config('tall-datatables.search_route'), '')
            : '';
    }

    /**
     * @return void
     *
     * @throws MissingTraitException
     */
    private function ensureAuthHasTrait(): void
    {
        if (! in_array(HasDatatableUserSettings::class, class_uses_recursive(Auth::user()))) {
            throw MissingTraitException::create(Auth::user()->getMorphClass(), HasDatatableUserSettings::class);
        }
    }
}
