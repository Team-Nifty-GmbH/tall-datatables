<?php

namespace TeamNiftyGmbH\DataTable\Exports\Concerns;

use Illuminate\Support\Str;
use TeamNiftyGmbH\DataTable\Formatters\BooleanFormatter;

trait ExportsData
{
    protected array $exportColumns = [];

    protected array $exportFormatters = [];

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

    public function mapRow($row): array
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

            if (! is_null($value) && isset($this->exportFormatters[$column])) {
                $formatter = $this->exportFormatters[$column];

                if ($formatter instanceof BooleanFormatter) {
                    $value = $value ? __('Yes') : __('No');
                } else {
                    $value = strip_tags($formatter->format($value, $rowArray));
                }
            }

            $result[$column] = $value;
        }

        return $result;
    }
}
