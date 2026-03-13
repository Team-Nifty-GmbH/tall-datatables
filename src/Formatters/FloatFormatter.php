<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class FloatFormatter implements Formatter
{
    public function __construct(public readonly bool $colored = false) {}

    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        $floatValue = (float) $value;

        $decimals = fmod($floatValue, 1) === 0.0 ? 2 : strlen(rtrim(substr((string) abs($floatValue), strpos((string) abs($floatValue), '.') + 1), '0'));
        $formatted = number_format($floatValue, $decimals, ',', '.');

        if (! $this->colored) {
            return $formatted;
        }

        if ($floatValue < 0) {
            return '<span class="text-red-600">' . $formatted . '</span>';
        }

        if ($floatValue > 0) {
            return '<span class="text-green-600">' . $formatted . '</span>';
        }

        return $formatted;
    }
}
