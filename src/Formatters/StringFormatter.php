<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class StringFormatter implements Formatter
{
    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        return e((string) $value);
    }
}
