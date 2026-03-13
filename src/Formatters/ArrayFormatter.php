<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class ArrayFormatter implements Formatter
{
    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        if (! is_array($value)) {
            return e((string) $value);
        }

        if (empty($value)) {
            return '';
        }

        // Check if it's a flat array of scalars
        $isFlat = array_reduce($value, fn (bool $carry, mixed $item) => $carry && is_scalar($item), true);

        if ($isFlat) {
            return implode(', ', array_map(fn (mixed $item) => e((string) $item), $value));
        }

        return '<pre class="text-xs">' . e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    }
}
