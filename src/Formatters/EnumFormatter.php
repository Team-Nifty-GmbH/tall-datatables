<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use Illuminate\Support\Str;
use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class EnumFormatter implements Formatter
{
    public function __construct(
        protected ?string $enumClass = null,
    ) {}

    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        if ($this->enumClass && enum_exists($this->enumClass)) {
            $enum = $this->enumClass::tryFrom($value);

            if ($enum) {
                return e(__(Str::headline($enum->name)));
            }
        }

        return e(__(Str::headline((string) $value)));
    }
}
