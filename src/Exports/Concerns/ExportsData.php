<?php

namespace TeamNiftyGmbH\DataTable\Exports\Concerns;

use Illuminate\Support\Str;
use TeamNiftyGmbH\DataTable\Formatters\ArrayFormatter;
use TeamNiftyGmbH\DataTable\Formatters\BooleanFormatter;
use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

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
            $formatter = $this->exportFormatters[$column] ?? null;

            if (is_null($value) && str_contains($column, '.')) {
                $value = $this->extractNestedValue($rowArray, explode('.', $column));

                if (is_array($value)) {
                    // Format every related value on its own, otherwise an element formatter
                    // (e.g. a boolean rendered as an icon) never sees the individual values.
                    $value = implode('; ', array_filter(
                        array_map(
                            fn ($item) => $this->formatExportValue($item, $formatter, $rowArray),
                            $value
                        ),
                        fn ($item) => $item !== null && $item !== ''
                    ));

                    $result[$column] = $value;

                    continue;
                }
            }

            if (! is_null($value) && $formatter) {
                $value = is_array($value)
                    ? strip_tags($formatter->format($value, $rowArray))
                    : $this->formatExportValue($value, $formatter, $rowArray);
            }

            $result[$column] = $value;
        }

        return $result;
    }

    /**
     * Resolve a dotted column path against a row array, descending through to-one segments
     * and mapping over to-many (list) segments at any position. This handles mixed paths such
     * as contact.contactTopics.name (a to-one relation followed by a to-many relation), which a
     * uniform wildcard cannot express.
     *
     * @param  array<int, string>  $segments
     */
    protected function extractNestedValue(mixed $data, array $segments): mixed
    {
        if ($segments === []) {
            return $data;
        }

        if (is_array($data) && array_is_list($data)) {
            $values = [];
            foreach ($data as $item) {
                $resolved = $this->extractNestedValue($item, $segments);

                if (is_array($resolved)) {
                    $values = array_merge($values, $resolved);
                } elseif (! is_null($resolved)) {
                    $values[] = $resolved;
                }
            }

            return $values;
        }

        $segment = array_shift($segments);

        return $this->extractNestedValue(data_get($data, $segment), $segments);
    }

    /**
     * Format a single scalar value for the export, unwrapping array formatters so their
     * element formatter decides. Boolean values are exported as text because their
     * formatter renders an icon, which strip_tags would reduce to an empty cell.
     */
    protected function formatExportValue(mixed $value, ?Formatter $formatter, array $context): mixed
    {
        if (is_null($value) || is_null($formatter)) {
            return $value;
        }

        if ($formatter instanceof ArrayFormatter) {
            $formatter = $formatter->elementFormatter() ?? $formatter;
        }

        if ($formatter instanceof BooleanFormatter) {
            return $value ? __('Yes') : __('No');
        }

        return strip_tags($formatter->format($value, $context));
    }
}
