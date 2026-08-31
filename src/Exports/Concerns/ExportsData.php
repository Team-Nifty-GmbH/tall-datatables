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
            }

            $result[$column] = $this->formatExportValue($value, $formatter, $rowArray);
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
     * Format a value for the export, unwrapping array formatters so their element formatter
     * decides. Boolean values are exported as text because their formatter renders an icon,
     * which strip_tags would reduce to an empty cell.
     */
    protected function formatExportValue(mixed $value, ?Formatter $formatter, array $context): mixed
    {
        if (is_null($value)) {
            return $value;
        }

        if ($formatter instanceof ArrayFormatter) {
            $formatter = $formatter->elementFormatter() ?? $formatter;
        }

        if (is_array($value) && ! $formatter instanceof ArrayFormatter) {
            // An associative array is a single structured value, not a set of values: a json
            // column such as a frozen snapshot carries its fields under their own names, and
            // handing each of them to a column formatter both fails and loses the names.
            if (! array_is_list($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // Format every element on its own. Only an ArrayFormatter reads an array; every
            // other formatter casts its input to string, which fails on one.
            return implode('; ', array_filter(
                array_map(
                    fn (mixed $item) => $this->formatExportValue($item, $formatter, $context),
                    $value
                ),
                fn (mixed $item) => $item !== null && $item !== ''
            ));
        }

        if (is_null($formatter)) {
            return $value;
        }

        if ($formatter instanceof BooleanFormatter) {
            return $value ? __('Yes') : __('No');
        }

        return $this->toPlainText($formatter->format($value, $context));
    }

    /**
     * Formatters escape their output for html. An exported cell is plain text, so the entities
     * have to be turned back into the characters the user actually typed. Tags are stripped
     * first, otherwise decoding would reintroduce markup that was deliberately escaped.
     */
    protected function toPlainText(string $value): string
    {
        return html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5);
    }
}
