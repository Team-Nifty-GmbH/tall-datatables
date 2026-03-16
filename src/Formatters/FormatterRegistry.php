<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class FormatterRegistry
{
    /** @var array<string, Formatter> */
    private array $registry = [];

    public function register(string $castClass, Formatter $formatter): static
    {
        $this->registry[$castClass] = $formatter;

        return $this;
    }

    public function resolve(string $castClass): Formatter
    {
        if (isset($this->registry[$castClass])) {
            return $this->registry[$castClass];
        }

        return $this->autoDetect($castClass);
    }

    /**
     * Resolve a formatter for a given column name using the model's casts array.
     *
     * @param  array<string, string>  $casts
     */
    public function resolveForColumn(string $column, array $casts): Formatter
    {
        if (! array_key_exists($column, $casts)) {
            return new StringFormatter();
        }

        $castValue = $casts[$column];

        // Handle "cast:params" strings like "date:Y-m-d"
        $castType = str_contains($castValue, ':')
            ? substr($castValue, 0, strpos($castValue, ':'))
            : $castValue;

        return $this->resolve($castType);
    }

    private function autoDetect(string $castClass): Formatter
    {
        return match (strtolower($castClass)) {
            'boolean', 'bool' => new BooleanFormatter(),
            'float', 'double', 'decimal' => new FloatFormatter(),
            'integer', 'int' => new StringFormatter(),
            'date', 'immutable_date' => new DateFormatter(mode: 'date'),
            'datetime', 'immutable_datetime', 'timestamp' => new DateFormatter(mode: 'datetime'),
            'image' => new ImageFormatter(),
            'array', 'json', 'collection' => new ArrayFormatter(),
            default => new StringFormatter(),
        };
    }
}
