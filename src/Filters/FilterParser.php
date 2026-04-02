<?php

namespace TeamNiftyGmbH\DataTable\Filters;

class FilterParser
{
    /**
     * Parse a text input string into a structured filter array.
     *
     * @return array{column: string, operator: string, value: mixed}|null
     */
    public function parse(string $text, string $column): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        // NULL / not null
        if ($text === 'NULL') {
            return ['column' => $column, 'operator' => 'is null', 'value' => null];
        }

        if ($text === '!NULL') {
            return ['column' => $column, 'operator' => 'is not null', 'value' => null];
        }

        // Range: value1..value2
        if (str_contains($text, '..')) {
            $parts = explode('..', $text, 2);
            $from = $this->castValue(trim($parts[0]));
            $to = $this->castValue(trim($parts[1]));

            return ['column' => $column, 'operator' => 'between', 'value' => [$from, $to]];
        }

        // Operator prefixes (order matters: longer operators first)
        $prefixMap = [
            '!=' => '!=',
            '>=' => '>=',
            '<=' => '<=',
            '=' => '=',
            '>' => '>',
            '<' => '<',
        ];

        foreach ($prefixMap as $prefix => $operator) {
            if (str_starts_with($text, $prefix)) {
                $value = $this->castValue(substr($text, strlen($prefix)));

                return ['column' => $column, 'operator' => $operator, 'value' => $value];
            }
        }

        // Plain text → like (escape SQL wildcards)
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $text);

        return ['column' => $column, 'operator' => 'like', 'value' => '%' . $escaped . '%'];
    }

    protected function castValue(string $value): mixed
    {
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }
}
