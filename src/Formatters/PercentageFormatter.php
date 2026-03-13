<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class PercentageFormatter implements Formatter
{
    public function __construct(public readonly bool $progressBar = false) {}

    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        $floatValue = (float) $value;

        if (! $this->progressBar) {
            $decimals = fmod($floatValue, 1) === 0.0 ? 0 : 2;

            return number_format($floatValue, $decimals, ',', '.') . ' %';
        }

        $clamped = max(0, min(100, $floatValue));
        $widthPercent = number_format($clamped, 2, '.', '');

        return '<div class="w-full bg-gray-200 rounded-full h-2">'
            . '<div class="bg-blue-600 h-2 rounded-full" style="width: ' . $widthPercent . '%"></div>'
            . '</div>';
    }
}
