<?php

namespace TeamNiftyGmbH\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FilterApplier
{
    public function __construct(
        protected array $countColumns = []
    ) {}

    /**
     * Apply an array of structured filter arrays to a query.
     *
     * @param  array<int, array{column: string, operator: string, value: mixed}>  $filters
     */
    public function apply(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter) {
            $query = $this->addFilter($query, $filter);
        }

        return $query;
    }

    protected function addCountFilter(Builder $query, string $column, string $operator, mixed $value): Builder
    {
        $relationName = Str::camel(Str::beforeLast($column, '_count'));

        return $query->has($relationName, $operator, (int) $value);
    }

    protected function addFilter(Builder $query, array $filter): Builder
    {
        $column = $filter['column'];
        $operator = $filter['operator'];
        $value = $filter['value'];

        // Count columns (verified relation counts) → has() query
        if (in_array($column, $this->countColumns)) {
            return $this->addCountFilter($query, $column, $operator, $value);
        }

        // Relation columns → whereHas
        if (str_contains($column, '.')) {
            return $this->addRelationFilter($query, $column, $operator, $value);
        }

        return match ($operator) {
            'like' => $query->where($column, 'like', $value),
            'between' => $query->whereBetween($column, $value),
            'is null' => $query->whereNull($column),
            'is not null' => $query->whereNotNull($column),
            default => $query->where($column, $operator, $value),
        };
    }

    protected function addRelationFilter(Builder $query, string $column, string $operator, mixed $value): Builder
    {
        $parts = explode('.', $column);
        $field = array_pop($parts);
        $relation = implode('.', $parts);

        return $query->whereHas($relation, function (Builder $q) use ($field, $operator, $value): void {
            $this->addFilter($q, ['column' => $field, 'operator' => $operator, 'value' => $value]);
        });
    }
}
