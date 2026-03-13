<?php

namespace TeamNiftyGmbH\DataTable\Formatters\Contracts;

interface Formatter
{
    /**
     * Format a raw value for display.
     *
     * @param  mixed  $value  The raw value from the model attribute
     * @param  array  $context  Full model attribute array for accessing sibling fields
     * @return string  Sanitized HTML string — use e() on any user-controlled data
     */
    public function format(mixed $value, array $context = []): string;
}
