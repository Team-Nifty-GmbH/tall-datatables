<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
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

    public function headings(): array
    {
        return collect($this->exportColumns)
            ->map(function ($column) {
                if (str_contains($column, '.')) {
                    $relation = explode('.', Str::beforeLast($column, '.'));
                    $columnName = Str::afterLast($column, '.');
                    $relation = array_map(fn ($part) => __(Str::headline($part)), $relation);

                    return implode(' -> ', $relation) . ' -> ' . __(Str::headline($columnName));
                }

                return __(Str::headline($column));
            })
            ->toArray();
    }

    public function map($row): array
    {
        $rowArray = $row->toArray();
        $result = [];

        foreach ($this->exportColumns as $column) {
            $value = data_get($rowArray, $column);

            if (is_null($value) && str_contains($column, '.')) {
                $parts = explode('.', $column);
                $lastPart = array_pop($parts);

                $wildcardKey = implode('.*.', $parts) . '.*.' . $lastPart;
                $value = data_get($rowArray, $wildcardKey);

                if (is_array($value)) {
                    $value = implode('; ', array_filter($value));
                }
            }

            $result[$column] = $value;
        }

        return $result;
    }

    public function query(): Relation|EloquentBuilder|Builder
    {
        return $this->builder;
    }
}
