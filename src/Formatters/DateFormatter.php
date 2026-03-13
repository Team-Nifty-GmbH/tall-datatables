<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use Carbon\Carbon;
use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;
use Throwable;

class DateFormatter implements Formatter
{
    public function __construct(
        public readonly string $mode = 'datetime',
        public readonly ?string $format = null
    ) {}

    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        try {
            if ($value instanceof Carbon) {
                $carbon = $value;
            } elseif (is_numeric($value)) {
                $carbon = Carbon::createFromTimestamp($value);
            } else {
                $carbon = Carbon::parse($value);
            }
        } catch (Throwable) {
            return '';
        }

        if ($this->format !== null) {
            return e($carbon->format($this->format));
        }

        return match ($this->mode) {
            'date' => e($carbon->format('d.m.Y')),
            'time' => e($carbon->format('H:i')),
            'relative', 'relativeTime' => e($carbon->diffForHumans()),
            default => e($carbon->format('d.m.Y H:i')),
        };
    }
}
