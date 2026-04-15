<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TeamNiftyGmbH\DataTable\ModelInfo\Attribute;

trait SupportsAggregation
{
    public array $aggregatable = ['*'];

    public array $aggregatableCols = [
        'sum' => [],
        'avg' => [],
        'min' => [],
        'max' => [],
        'count' => [],
    ];

    protected array $aggregatableRelationCols = [];

    public function applyAggregations(): void
    {
        $allAggregatedCols = collect($this->aggregatableCols)->flatten()->unique()->values()->toArray();
        $missing = array_diff($allAggregatedCols, $this->enabledCols);

        if (! empty($missing)) {
            $this->enabledCols = array_values(array_unique(array_merge($this->enabledCols, $missing)));
            $this->colLabels = $this->getColLabels();
        }

        $this->cacheState();
        $this->loadData();
    }

    protected function getAggregatable(): array
    {
        return once(function () {
            $foreignKeys = Cache::remember(
                'foreign-keys:' . $this->modelTable,
                86400,
                fn () => array_filter(
                    Schema::getColumnListing($this->modelTable),
                    fn (string $col) => str_ends_with($col, '_id') || str_ends_with($col, '_by')
                ),
            );

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
        });
    }

    protected function getAggregatableRelationCols(): array
    {
        return [];
    }

    protected function getAggregate(Builder $builder): array
    {
        $aggregates = [];
        foreach ($this->aggregatableCols as $type => $columns) {
            if (! in_array($type, ['sum', 'avg', 'min', 'max', 'count'])) {
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
                    $qualifiedColumn = str_contains($column, '.')
                        ? $column
                        : $this->modelTable . '.' . $column;
                    $aggregates[$type][$column] = $builder->{$type}($qualifiedColumn);
                } catch (QueryException $e) {
                    $this->toast()->error($e->getMessage());

                    continue;
                }
            }
        }

        return $aggregates;
    }
}
