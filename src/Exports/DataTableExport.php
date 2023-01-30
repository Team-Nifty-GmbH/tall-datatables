<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DataTableExport implements FromQuery, ShouldAutoSize, WithHeadings
{
    use Exportable;

    private EloquentBuilder $builder;

    private string $model;

    private array $exportColumns;

    /**
     * @param EloquentBuilder $builder
     * @param string $model
     * @param array $exportColumns
     */
    public function __construct(EloquentBuilder $builder, string $model, array $exportColumns = [])
    {
        $this->builder = $builder;
        $this->model = $model;
        $this->exportColumns = $exportColumns;
    }

    /**
     * @return Builder|EloquentBuilder|Relation
     */
    public function query(): Relation|EloquentBuilder|Builder
    {
        return $this->builder;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return collect($this->exportColumns ?: array_keys($this->builder->first()->toArray()))
        ->map(function ($column) {
            return __($column);
        })
        ->toArray();
    }
}
