<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class PercentageFormatter implements Formatter
{
    public function __construct(
        public readonly bool $progressBar = false,
        public readonly float $multiplier = 100,
    ) {}

    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        $floatValue = (float) bcmul((string) $value, (string) $this->multiplier, 10);

        if (! $this->progressBar) {
            $decimals = fmod($floatValue, 1) === 0.0 ? 0 : 2;

            return number_format($floatValue, $decimals, ',', '.') . ' %';
        }

        $clamped = max(0, min(100, $floatValue));
        $widthPercent = number_format($clamped, 2, '.', '');
        $decimals = fmod($floatValue, 1) === 0.0 ? 0 : 2;
        $label = number_format($floatValue, $decimals, ',', '.') . ' %';

        return '<div>'
            . '<div class="overflow-hidden rounded-full bg-gray-200 h-2 dark:bg-gray-700">'
            . '<div class="h-2 rounded-full bg-indigo-500 dark:bg-indigo-700" style="width: ' . $widthPercent . '%"></div>'
            . '</div>'
            . '<span class="text-xs text-gray-500 dark:text-gray-400">' . $label . '</span>'
            . '</div>';
    }
}
