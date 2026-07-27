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
                // An enum that brings its own label knows better than a headline
                // of its case name, which cannot express a wording the case name
                // does not already contain.
                return e(
                    method_exists($enum, 'label')
                        ? $enum->label()
                        : __(Str::headline($enum->name))
                );
            }
        }

        return e(__(Str::headline((string) $value)));
    }
}
