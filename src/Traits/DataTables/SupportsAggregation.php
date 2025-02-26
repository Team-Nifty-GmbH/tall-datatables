<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;
use Spatie\ModelInfo\Attributes\Attribute;

trait SupportsAggregation
{
    public array $aggregatableCols = [
        'sum' => [],
        'avg' => [],
        'min' => [],
        'max' => [],
    ];

    public array $aggregatable = ['*'];

    protected array $aggregatableRelationCols = [];

    #[Renderless]
    public function applyAggregations(): void
    {
        $this->cacheState();
        $this->loadData();
    }

    protected function getAggregatable(): array
    {
        $foreignKeys = collect(
            Cache::remember(
                'column-listing:' . $this->modelTable,
                86400,
                fn () => Schema::getColumnListing($this->modelTable),
            ),
        )
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

    protected function getAggregatableRelationCols(): array
    {
        return [];
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
                    $this->toast()->error($e->getMessage());

                    continue;
                }
            }
        }

        return $aggregates;
    }
}
