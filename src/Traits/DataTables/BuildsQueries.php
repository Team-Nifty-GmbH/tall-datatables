<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Formatters\ArrayFormatter;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;
use TeamNiftyGmbH\DataTable\Formatters\StringFormatter;
use TeamNiftyGmbH\DataTable\Helpers\SessionFilter;
use Throwable;

trait BuildsQueries
{
    /**
     * Add a single filter to the query.
     */
    protected function addFilter(Builder $query, string|int $type, array|string $filter): void
    {
        // Handle simple key-value filters like ['column_name' => 'value']
        if (is_string($filter)) {
            $filter = [
                'column' => (string) $type,
                'operator' => '=',
                'value' => $filter,
            ];
            $type = 0;
        }

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
                    $this->applyFilterWhereHas($query, $filter['relation']);
                } elseif ($filter['value'] === '%!*%') {
                    $this->applyFilterWhereDoesntHave($query, $filter['relation']);
                } else {
                    $query->whereHas($filter['relation'], function (Builder $subQuery) use ($type, $filter) {
                        unset($filter['relation']);
                        $this->addFilter($subQuery, $type, $filter);

                        return $subQuery;
                    });
                }
            } elseif (in_array($filter['operator'], ['is null', 'is not null'])) {
                $this->applyFilterWhereNull($query, $filter);
            } elseif ($filter['operator'] === 'between') {
                $query->whereBetween($filter['column'], $filter['value']);
            } else {
                $column = $filter['column'] ?? null;
                $operator = $filter['operator'] ?? null;
                $value = $filter['value'] ?? null;

                if ($column && $operator && ($value !== null && $value !== '')) {
                    $query->where($column, $operator, $value);
                }
            }

            return;
        }

        $method = $this->getFilterMethodName($type);
        if (method_exists($this, $method)) {
            $this->{$method}($query, $filter);
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

    /**
     * Apply server-side formatting to enabled columns using the FormatterRegistry.
     *
     * Wraps formatted values as ['raw' => mixed, 'display' => string].
     * Plain values that don't need formatting remain unwrapped for smaller payloads.
     */
    protected function applyFormatters(array &$itemArray, Model $item, array $context): void
    {
        $registry = app(FormatterRegistry::class);
        $customFormatters = $this->getFormatters();
        $modelCasts = $item->getCasts();

        foreach ($this->enabledCols as $col) {
            if (! array_key_exists($col, $itemArray)) {
                continue;
            }

            $raw = $itemArray[$col];
            $baseCol = str_contains($col, '.') ? last(explode('.', $col)) : $col;

            if (isset($customFormatters[$col]) && is_string($customFormatters[$col])) {
                $formatter = $registry->resolve($customFormatters[$col]);
            } else {
                $casts = str_contains($col, '.')
                    ? $this->resolveCastsForColumn($item, $col)
                    : $modelCasts;

                // Filter out non-string cast values (e.g. array-based casts like [CastClass::class, 'param'])
                $casts = array_filter($casts, 'is_string');

                $formatter = $registry->resolveForColumn($baseCol, $casts);
            }

            // Use ArrayFormatter for array values when StringFormatter would fail
            if (is_array($raw) && $formatter instanceof StringFormatter) {
                $formatter = new ArrayFormatter();
            }

            $display = $formatter->format($raw, $context);
            $rawString = is_null($raw)
                ? ''
                : (is_array($raw) ? json_encode($raw) : (string) $raw);

            if ($display !== e($rawString)) {
                $itemArray[$col] = ['raw' => $raw, 'display' => $display];
            }
        }
    }

    /**
     * Build the base query with filters, sorting, relations and session filters applied.
     */
    protected function buildSearch(bool $unpaginated = false): Builder
    {
        /** @var Model $model */
        $model = $this->getModel();

        foreach ($this->getAggregatableRelationCols() as $aggregatableRelationCol) {
            $this->aggregatableRelationCols[] = $aggregatableRelationCol->alias;
        }

        if ($this->search && method_exists($model, 'search') && ! $unpaginated) {
            $query = $this->getScoutSearch()->toEloquentBuilder($this->enabledCols, $this->perPage, $this->page);
        } else {
            $query = $model::query();
        }

        if ($this->withSoftDeletes && $this->allowSoftDeletes()) {
            $query->withTrashed();
        }

        $this->applySorting($query);

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

        $this->applySessionFilter($query);

        return $this->applyFilters($query);
    }

    protected function getIsSearchable(): bool
    {
        return is_null($this->isSearchable)
            ? in_array(Searchable::class, class_uses_recursive($this->getModel()))
            : $this->isSearchable;
    }

    protected function getPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        return $paginator;
    }

    /**
     * Execute the query and return paginated results.
     */
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

    protected function getScoutSearch(): \Laravel\Scout\Builder
    {
        return $this->getModel()::search($this->search);
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

        $this->applyFormatters($itemArray, $item, $rawArray);

        return $itemArray;
    }

    /**
     * Parse a filter array, handling date conversion and calculations.
     */
    protected function parseFilter(array $filter): array
    {
        $filter = Arr::only($filter, ['column', 'operator', 'value', 'relation']);

        if (! array_key_exists('value', $filter)) {
            return $filter;
        }

        $filter['value'] = is_array($filter['value']) ? $filter['value'] : [$filter['value']];

        $filter['value'] = collect($filter['value'])
            ->map(function ($value) use (&$filter) {
                if (is_string($value) && ! is_numeric($value)) {
                    $dateFormats = ['d.m.Y', 'd/m/Y', 'm/d/Y', 'Y-m-d'];
                    $dateTimeFormats = ['d.m.Y H:i', 'd/m/Y H:i', 'Y-m-d H:i:s', 'd.m.Y H:i:s', 'd/m/Y H:i:s'];

                    foreach ($dateFormats as $format) {
                        try {
                            $date = Carbon::createFromFormat($format, $value);

                            return $date->startOfDay()->format('Y-m-d H:i:s');
                        } catch (InvalidFormatException) {
                            continue;
                        }
                    }

                    foreach ($dateTimeFormats as $format) {
                        try {
                            $date = Carbon::createFromFormat($format, $value);

                            return $date->format('Y-m-d H:i:s');
                        } catch (InvalidFormatException) {
                            continue;
                        }
                    }

                    try {
                        $date = Carbon::parse($value);

                        $hasTime = preg_match('/\d{1,2}:\d{2}/', $value);

                        if (! $hasTime) {
                            return $date->startOfDay()->format('Y-m-d H:i:s');
                        }

                        return $date->format('Y-m-d H:i:s');
                    } catch (InvalidFormatException) {
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

    /**
     * Apply fixed and user filters to the query.
     */
    private function applyFilters(Builder $builder): Builder
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

            $method = $this->getFilterMethodName($type);
            if (method_exists($this, $method)) {
                $this->{$method}($builder, $filter);
            }
        }

        // add user filters
        $builder->where(function ($query): void {
            foreach ($this->userFilters as $index => $orFilter) {
                $query->where(function (Builder $query) use ($orFilter): void {
                    foreach ($orFilter as $type => $filter) {
                        $this->addFilter($query, $type, $filter);
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

    private function applyFilterWhere(Builder $builder, array $filter): Builder
    {
        return $builder->where($filter);
    }

    private function applyFilterWhereDoesntHave(Builder $builder, string $relation): Builder
    {
        return $builder->whereDoesntHave(Str::camel($relation));
    }

    private function applyFilterWhereHas(Builder $builder, string $relation): Builder
    {
        return $builder->whereHas(Str::camel($relation));
    }

    private function applyFilterWhereIn(Builder $builder, array $filter): Builder
    {
        return $builder->whereIn($filter[0], $filter[1]);
    }

    private function applyFilterWhereNull(Builder $builder, array $filter): Builder
    {
        return $builder->whereNull(
            columns: $filter['column'],
            boolean: ($filter['boolean'] ?? 'and') !== 'or' ? 'and' : 'or',
            not: $filter['operator'] === 'is not null'
        );
    }

    private function applyFilterWith(Builder $builder, array $filter): Builder
    {
        return $builder->with($filter);
    }

    /**
     * Apply session filters to the query if present.
     */
    private function applySessionFilter(Builder $query): void
    {
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
    }

    /**
     * Apply sorting to the query based on user or default ordering.
     */
    private function applySorting(Builder $query): void
    {
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
    }

    /**
     * Get the method name for a filter type.
     */
    private function getFilterMethodName(string $type): string
    {
        return 'applyFilter' . ucfirst($type);
    }

    /**
     * Resolve the casts array for a dot-notation column by traversing relations.
     *
     * @return array<string, string>
     */
    private function resolveCastsForColumn(Model $item, string $col): array
    {
        $parts = explode('.', $col);
        array_pop($parts);

        try {
            $related = $item;
            foreach ($parts as $segment) {
                $related = $related->{$segment}()->getRelated();
            }

            return $related->getCasts();
        } catch (Throwable) {
            return [];
        }
    }
}
