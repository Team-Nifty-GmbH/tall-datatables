<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use BadMethodCallException;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
     * Apply server-side formatting to enabled columns using the FormatterRegistry.
     *
     * Wraps formatted values as ['raw' => mixed, 'display' => string].
     * Plain values that don't need formatting remain unwrapped for smaller payloads.
     */
    /** @internal Resolved formatters cache — built once per loadData(), reused for all rows */
    private ?array $resolvedFormatters = null;

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
                    try {
                        $query->whereHas($filter['relation'], function (Builder $subQuery) use ($type, $filter) {
                            unset($filter['relation']);
                            $this->addFilter($subQuery, $type, $filter);

                            return $subQuery;
                        });
                    } catch (BadMethodCallException) {
                        // Relation does not exist on model — skip this filter
                    }
                }
            } elseif (in_array($filter['operator'], ['is null', 'is not null'])) {
                $this->applyFilterWhereNull($query, $filter);
            } else {
                try {
                    if ($filter['operator'] === 'between') {
                        if (is_array($filter['value']) && count($filter['value']) === 2) {
                            $query->whereBetween($filter['column'], $filter['value']);
                        }
                    } else {
                        $column = $filter['column'] ?? null;
                        $operator = $filter['operator'] ?? null;
                        $value = $filter['value'] ?? null;

                        if ($column && $operator && ($value !== null && $value !== '')) {
                            $query->where($column, $operator, $value);
                        }
                    }
                } catch (Throwable) {
                    // Invalid filter value — skip silently
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
        return Cache::memo()->rememberForever(
            'dt:soft-deletes:' . $this->getModel(),
            fn () => in_array(
                SoftDeletes::class,
                class_uses_recursive($this->getModel())
            )
        );
    }

    protected function applyFormatters(array &$itemArray, Model $item, array $context): void
    {
        $formatters = $this->getResolvedFormatters($item);

        $context['_dbTimezone'] = $this->getDatabaseTimezone();
        $context['_displayTimezone'] = $this->getDisplayTimezone();

        foreach ($this->enabledCols as $col) {
            if (! array_key_exists($col, $itemArray)) {
                continue;
            }

            $raw = $itemArray[$col];
            $formatter = $formatters[$col] ?? null;

            if (! $formatter) {
                continue;
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
            try {
                $query = $this->getScoutSearch()->toEloquentBuilder($this->enabledCols, $this->perPage, $this->page);
            } catch (Throwable) {
                $query = $model::query();
                $this->applyFallbackSearch($query, $this->search);
            }
        } elseif ($this->search && ! $unpaginated) {
            $query = $model::query();
            $this->applyFallbackSearch($query, $this->search);
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
        if (! is_null($this->isSearchable)) {
            return $this->isSearchable;
        }

        return Cache::memo()->rememberForever(
            'dt:searchable:' . $this->getModel(),
            fn () => in_array(Searchable::class, class_uses_recursive($this->getModel()))
        );
    }

    protected function getPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        return $paginator;
    }

    /**
     * Build the formatter map once, then reuse for all rows.
     */
    protected function getResolvedFormatters(Model $item): array
    {
        if ($this->resolvedFormatters !== null) {
            return $this->resolvedFormatters;
        }

        $registry = app(FormatterRegistry::class);
        $customFormatters = $this->getFormatters();
        $modelCasts = $item->getCasts();
        $this->resolvedFormatters = [];

        foreach ($this->enabledCols as $col) {
            $baseCol = str_contains($col, '.') ? last(explode('.', $col)) : $col;

            if (isset($customFormatters[$col]) && is_string($customFormatters[$col])) {
                $this->resolvedFormatters[$col] = $registry->resolve($customFormatters[$col]);
            } elseif (isset($customFormatters[$col]) && is_array($customFormatters[$col])) {
                $formatterName = $customFormatters[$col][0] ?? 'string';
                $formatterOptions = $customFormatters[$col][1] ?? [];
                $this->resolvedFormatters[$col] = $registry->resolveWithOptions($formatterName, $formatterOptions);
            } else {
                $casts = str_contains($col, '.')
                    ? $this->resolveCastsForColumn($item, $col)
                    : $modelCasts;

                $castValue = $casts[$baseCol] ?? null;

                if (is_array($castValue) && count($castValue) >= 1) {
                    $this->resolvedFormatters[$col] = $registry->resolveWithOptions($castValue[0] ?? 'string', $castValue[1] ?? []);
                } else {
                    $stringCasts = array_filter($casts, 'is_string');
                    $this->resolvedFormatters[$col] = $registry->resolveForColumn($baseCol, $stringCasts);
                }
            }
        }

        return $this->resolvedFormatters;
    }

    /**
     * Execute the query and return paginated results.
     */
    protected function getResultFromQuery(Builder $query): LengthAwarePaginator|Collection|array
    {
        try {
            if (property_exists($query, 'scout_pagination')) {
                $items = $query->get();
                $hasAdditionalFilters = ! empty($this->userFilters);

                if ($hasAdditionalFilters) {
                    // When additional filters are applied on top of Scout results,
                    // the Meilisearch estimatedTotalHits is inaccurate — use actual count
                    $total = $query->toBase()->getCountForPagination();
                } else {
                    $total = max(data_get($query->scout_pagination, 'estimatedTotalHits'), $query->count());
                }

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

                    // Apply Scout highlights without destroying formatters
                    foreach ($this->enabledCols as $key) {
                        $highlighted = data_get($query->hits, $item->getKey() . '._formatted.' . $key);
                        if ($highlighted === null) {
                            continue;
                        }

                        // If already formatted (has display key), replace the display with highlighted version
                        if (is_array($itemArray[$key] ?? null) && isset($itemArray[$key]['display'])) {
                            continue;
                        }

                        // Wrap highlighted value as display so {!! !!} renders the <mark> tags
                        $raw = $itemArray[$key] ?? $highlighted;
                        $itemArray[$key] = ['raw' => $raw, 'display' => $highlighted];
                    }

                    return $itemArray;
                }
            );
        } else {
            $registry = app(FormatterRegistry::class);
            $customFormatters = $this->getFormatters();

            $dbTimezone = $this->getDatabaseTimezone();
            $displayTimezone = $this->getDisplayTimezone();

            $mapped = $resultCollection->map(
                function (Model $item) use ($registry, $customFormatters, $dbTimezone, $displayTimezone) {
                    $itemArray = $this->itemToArray($item);
                    $itemArray['_dbTimezone'] = $dbTimezone;
                    $itemArray['_displayTimezone'] = $displayTimezone;

                    // Re-apply formatters to columns that were added after parent::itemToArray()
                    // (e.g. avatar set in child class override) and not yet formatted
                    foreach ($this->enabledCols as $col) {
                        if (! array_key_exists($col, $itemArray)) {
                            continue;
                        }

                        // Skip already formatted values
                        if (is_array($itemArray[$col]) && isset($itemArray[$col]['display'])) {
                            continue;
                        }

                        $raw = $itemArray[$col];

                        if (isset($customFormatters[$col])) {
                            $formatter = is_string($customFormatters[$col])
                                ? $registry->resolve($customFormatters[$col])
                                : $registry->resolveWithOptions($customFormatters[$col][0] ?? 'string', $customFormatters[$col][1] ?? []);

                            $display = $formatter->format($raw, $itemArray);
                            $rawString = is_null($raw) ? '' : (is_array($raw) ? json_encode($raw) : (string) $raw);

                            if ($display !== e($rawString)) {
                                $itemArray[$col] = ['raw' => $raw, 'display' => $display];
                            }
                        }
                    }

                    return $itemArray;
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

        $this->augmentItemArray($itemArray, $item);
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

        $column = $filter['column'] ?? '';

        $filter['value'] = collect($filter['value'])
            ->map(function ($value) use ($column) {
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

                    // Only attempt permissive Carbon::parse() on date-type columns
                    // to avoid converting words like "january" or "yesterday" to dates
                    if ($this->isDateColumn($column)) {
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

    private function applyFallbackSearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $q) use ($search): void {
            foreach ($this->enabledCols as $col) {
                if (str_contains($col, '.')) {
                    $parts = explode('.', $col);
                    $column = array_pop($parts);
                    $relation = implode('.', array_map(fn ($p) => Str::camel($p), $parts));

                    $q->orWhereHas($relation, fn (Builder $sub) => $sub->where($column, 'like', '%' . $search . '%'));
                } else {
                    $q->orWhere($col, 'like', '%' . $search . '%');
                }
            }
        });
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

        // Unified filter application — all groups in one format
        // Filters within a group are AND, groups are OR with each other
        if (! empty($this->userFilters)) {
            // Migrate old format if needed
            $filters = $this->migrateFilterFormat($this->userFilters);

            $builder->where(function ($query) use ($filters): void {
                foreach (array_values($filters) as $index => $orFilter) {
                    if (! is_array($orFilter)) {
                        continue;
                    }

                    $query->where(function (Builder $query) use ($orFilter): void {
                        foreach ($orFilter as $type => $filter) {
                            $this->addFilter($query, $type, $filter);
                        }
                    }, boolean: $index > 0 ? 'or' : 'and');
                }
            });
        }

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
        try {
            return $builder->whereDoesntHave(Str::camel($relation));
        } catch (BadMethodCallException) {
            return $builder;
        }
    }

    private function applyFilterWhereHas(Builder $builder, string $relation): Builder
    {
        try {
            return $builder->whereHas(Str::camel($relation));
        } catch (BadMethodCallException) {
            return $builder;
        }
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

    private function isDateColumn(string $column): bool
    {
        $formatters = $this->getFormatters();
        $cast = $formatters[$column] ?? null;

        if (is_string($cast) && in_array($cast, ['date', 'datetime', 'immutable_date', 'immutable_datetime'])) {
            return true;
        }

        // Check raw column name in model casts
        $parts = explode('.', $column);
        $col = end($parts);

        return str_contains($col, 'date') || str_ends_with($col, '_at');
    }

    /**
     * Migrate old userFilters format (with 'text' key) to unified format.
     */
    private function migrateFilterFormat(array $filters): array
    {
        if (! isset($filters['text'])) {
            return $filters;
        }

        // Old format — convert text filters to group 0
        $textGroup = [];
        foreach ($filters['text'] as $col => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            $parsed = $this->parseTextFilterValue($value, $col);
            $parsed['source'] = 'text';
            $textGroup[] = $parsed;

            // Also populate textFilters for input restoration
            $this->textFilters[$col] = $value;
        }

        unset($filters['text']);

        $sidebarGroups = array_values(
            array_filter($filters, fn ($v, $k) => is_numeric($k) && is_array($v), ARRAY_FILTER_USE_BOTH)
        );

        $result = $textGroup ? array_merge([$textGroup], $sidebarGroups) : $sidebarGroups;

        // Persist migrated format
        $this->userFilters = $result;

        return $result;
    }

    private function normalizeFilterValue(string $value, string $column): string|float
    {
        if ($this->isDateColumn($column)) {
            $date = $this->parseDateValue(trim($value));
            if ($date) {
                return $date;
            }
        }

        return $this->normalizeNumericValue($value);
    }

    private function normalizeNumericValue(string $value): string|float
    {
        $trimmed = trim($value);

        // Already valid numeric (English format or integer)
        if (is_numeric($trimmed)) {
            return (float) $trimmed;
        }

        // Detect which separator is the decimal: the LAST comma or dot
        $lastComma = strrpos($trimmed, ',');
        $lastDot = strrpos($trimmed, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // Both present — last one is the decimal separator
            if ($lastComma > $lastDot) {
                // 1.234,56 → comma is decimal
                $normalized = str_replace('.', '', $trimmed);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                // 1,234.56 → dot is decimal
                $normalized = str_replace(',', '', $trimmed);
            }

            return is_numeric($normalized) ? (float) $normalized : $trimmed;
        }

        if ($lastComma !== false) {
            // Only comma: treat as decimal if 1-2 digits follow (39,99 → 39.99)
            $afterComma = substr($trimmed, $lastComma + 1);
            if (strlen($afterComma) <= 2) {
                $normalized = str_replace(',', '.', $trimmed);

                return is_numeric($normalized) ? (float) $normalized : $trimmed;
            }
        }

        return $trimmed;
    }

    private function parseDateValue(string $value): ?string
    {
        // Full date: dd.mm.yyyy or dd/mm/yyyy
        if (preg_match('#^(\d{1,2})[./](\d{1,2})[./](\d{4})$#', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Partial: dd.mm or mm.yyyy
        if (preg_match('#^(\d{1,2})[./](\d{1,2})$#', $value, $m)) {
            // If second part > 12, treat as dd.mm with implicit current year
            if ((int) $m[2] > 12) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', (int) date('Y'), (int) $m[2], (int) $m[1]);
        }

        // Already in Y-m-d format
        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $value)) {
            return $value;
        }

        // Partial Y-m
        if (preg_match('#^(\d{4})-(\d{1,2})$#', $value)) {
            return $value;
        }

        return null;
    }

    private function parseTextFilterValue(string $value, string $column): array
    {
        $trimmed = trim($value);

        // Support "is null" / "is not null" (case insensitive)
        if (preg_match('/^(is\s+null|is\s+not\s+null)$/i', $trimmed, $matches)) {
            return [
                'column' => $column,
                'operator' => strtolower(preg_replace('/\s+/', ' ', $matches[1])),
                'value' => null,
            ];
        }

        // Support !* (whereDoesntHave) and * (whereHas) for relation columns
        if ($trimmed === '!*') {
            return [
                'column' => $column,
                'operator' => 'like',
                'value' => '%!*%',
            ];
        }

        if ($trimmed === '*') {
            return [
                'column' => $column,
                'operator' => 'like',
                'value' => '%*%',
            ];
        }

        // Support operator prefixes: >=, <=, !=, >, <, =
        if (preg_match('/^(>=|<=|!=|>|<|=)\s*(.+)$/', $trimmed, $matches)) {
            $filterValue = $this->normalizeFilterValue($matches[2], $column);

            return [
                'column' => $column,
                'operator' => $matches[1],
                'value' => $filterValue,
            ];
        }

        // Columns with value lists (enums, states, booleans) use exact match
        if (isset($this->filterValueLists[$column])) {
            return [
                'column' => $column,
                'operator' => '=',
                'value' => $value,
            ];
        }

        // Date columns: parse localized date format to Y-m-d
        if ($this->isDateColumn($column)) {
            $date = $this->parseDateValue($trimmed);
            if ($date) {
                return [
                    'column' => $column,
                    'operator' => 'like',
                    'value' => $date . '%',
                ];
            }
        }

        return [
            'column' => $column,
            'operator' => 'like',
            'value' => '%' . $value . '%',
        ];
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
