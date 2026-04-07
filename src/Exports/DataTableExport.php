<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;

class DataTableExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable, ExportsData;

    private EloquentBuilder $builder;

    public function __construct(EloquentBuilder $builder, array $exportColumns = [])
    {
        $this->builder = $builder;
        $this->exportColumns = $exportColumns;
    }

    public function map($row): array
    {
        return $this->mapRow($row);
    }

    public function query(): Relation|EloquentBuilder|Builder
    {
        return $this->builder;
    }
}
