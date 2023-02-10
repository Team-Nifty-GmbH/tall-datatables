<?php

namespace TeamNiftyGmbH\DataTable;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
use Spatie\ModelInfo\Attributes\Attribute;
use Spatie\ModelInfo\Relations\Relation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TeamNiftyGmbH\DataTable\Exports\DataTableExport;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;
use TeamNiftyGmbH\DataTable\Traits\HasFrontendAttributes;
use TeamNiftyGmbH\DataTable\Tratis\WithLockedPublicPropertiesTrait;
use WireUi\Traits\Actions;

class DataTable extends Component
{
    use Actions, WithLockedPublicPropertiesTrait;

    public bool $initialized = false;

    protected string $model;

    /** @locked  */
    public array $filters = [];

    protected array $filtersCached;

    /** @locked  */
    public array $availableCols = [];

    /** @locked  */
    public array $availableRelations = [];

    public string $modelName;

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

    public ?bool $isSearchable = null;

    /** @locked  */
    public bool $isExportable = true;

    /** @locked  */
    public bool $isFilterable = true;

    public string $search = '';

    public string $orderBy = '';

    public bool $orderAsc = true;

    public string $page = '1';

    public int $perPage = 15;

    public array $colLabels = [];

    public array $stretchCol = [];

    public array $sortable = [];

    public array $aggregatable = [];

    public bool $selectable = false;

    public array $selected = [];

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
            ? array_fill_keys($tableFields->pluck('name')->toArray(), true)
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

        $this->modelName = class_basename($this->model);

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

        $returnKeys = $this->getReturnKeys();

        $aggregates = [];
        if (property_exists($query, 'hits')) {
            $mapped = $resultCollection->map(
                function ($item) use ($query, $returnKeys) {
                    $itemArray = Arr::only(Arr::dot($this->itemToArray($item)), $returnKeys);

                    foreach ($itemArray as $key => $value) {
                        $itemArray[$key] = data_get($query->hits, $item->getKey() . '._formatted.' . $key, $value);
                    }

                    return $itemArray;
                }
            );
        } else {
            // only return the columns that are available
            $mapped = $resultCollection->map(
                function ($item) use ($returnKeys) {
                    return Arr::only(
                        Arr::dot($this->itemToArray($item)),
                        $returnKeys
                    );
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
            [
                (new $this->model)->getKeyName(),
                'href',
            ]
        );
    }

    /**
     * @param $item
     * @return array
     */
    public function itemToArray($item): array
    {
        if ($appends = $this->getAppends()) {
            $item->append($appends);
        }

        return $item->toArray();
    }

    /**
     * @return array
     */
    public function getAppends(): array
    {
        return array_merge(
            $this->appends,
            in_array(HasFrontendAttributes::class, class_uses_recursive($this->model)) ? ['href'] : []
        );
    }

    /**
     * @param string $name
     * @param bool $permanent
     * @return void
     *
     * @throws \Exception
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
     * @throws \Exception
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
        /** @var $model Model **/
        $model = $this->model;

        if ($this->search && method_exists($model, 'search')) {
            $query = $model::search($this->search)
                ->toEloquentBuilder($this->enabledCols, $this->perPage, $this->page);
        } else {
            $query = $model::query();
        }

        if ($this->orderBy) {
            $query->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc');
        } else {
            $query->orderBy((new $model)->getKeyName(), 'desc');
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

        return $relatedModel::all()->map(function (Model $item) {
            return [
                'value' => $item->getKey(),
                'label' => $item->name,
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
     * @throws \Exception
     */
    private function ensureAuthHasTrait()
    {
        if (! method_exists(Auth::user(), 'datatableUserSettings')) {
            throw new \Exception(Auth::user()->getMorphClass() . ' must use HasDatatableUserSettings trait');
        }
    }
}
