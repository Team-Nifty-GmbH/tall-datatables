<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use NumberFormatter as IntlNumberFormatter;
use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class MoneyFormatter implements Formatter
{
    public function __construct(public readonly bool $colored = false) {}

    public function format(mixed $value, array $context = []): string
    {
        if (! is_numeric($value)) {
            return '';
        }

        $currencyCode = e(data_get($context, 'currency.iso') ?? data_get($context, 'currency_code') ?? 'EUR');

        $locale = app()->getLocale();
        $formatter = new IntlNumberFormatter($locale, IntlNumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency((float) $value, $currencyCode);

        if (! $this->colored) {
            return e($formatted);
        }

        $escaped = e($formatted);
        // Not bccomp: a float like -1.3E-8 casts to scientific notation, which bcmath rejects.
        $sign = (float) $value <=> 0.0;

        if ($sign === -1) {
            return '<span class="text-red-600">' . $escaped . '</span>';
        }

        if ($sign === 1) {
            return '<span class="text-green-600">' . $escaped . '</span>';
        }

        return $escaped;
    }
}
