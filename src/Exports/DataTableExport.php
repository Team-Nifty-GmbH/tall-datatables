<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DataTableExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    private EloquentBuilder $builder;

    private array $exportColumns;

    public function __construct(EloquentBuilder $builder, array $exportColumns = [])
    {
        $this->builder = $builder;
        $this->exportColumns = $exportColumns;
    }

    public function query(): Relation|EloquentBuilder|Builder
    {
        return $this->builder;
    }

    public function headings(): array
    {
        return collect($this->exportColumns)
            ->map(function ($column) {
                return __($column);
            })
            ->toArray();
    }

    public function map($row): array
    {
        return array_merge(
            array_fill_keys($this->exportColumns, null),
            Arr::only(Arr::dot($row->toArray()), $this->exportColumns)
        );
    }
}
