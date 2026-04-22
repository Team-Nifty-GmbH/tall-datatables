<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class DurationFormatter implements Formatter
{
    public function __construct(public readonly bool $showSeconds = false) {}

    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        $ms = (int) $value;
        $negative = $ms < 0;
        $ms = abs($ms);

        $totalSeconds = intdiv($ms, 1000);
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;

        $formatted = sprintf('%02d:%02d', $hours, $minutes);

        if ($this->showSeconds) {
            $formatted .= sprintf(':%02d', $seconds);
        }

        return $negative ? '-' . $formatted : $formatted;
    }
}
