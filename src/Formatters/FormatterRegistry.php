<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class FormatterRegistry
{
    /** @var array<string, Formatter> */
    protected array $registry = [];

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

    protected function autoDetect(string $castClass): Formatter
    {
        return match (strtolower($castClass)) {
            // Standard Eloquent casts
            'boolean', 'bool' => new BooleanFormatter(),
            'float', 'double', 'decimal' => new FloatFormatter(),
            'integer', 'int' => new StringFormatter(),
            'date', 'immutable_date' => new DateFormatter(mode: 'date'),
            'datetime', 'immutable_datetime', 'timestamp' => new DateFormatter(mode: 'datetime'),
            'image' => new ImageFormatter(),
            'array', 'json', 'collection' => new ArrayFormatter(),
            // v1 JS formatter names (backwards compatibility via typeScriptAttributes)
            'money' => new MoneyFormatter(),
            'coloredmoney' => new MoneyFormatter(colored: true),
            'percentage' => new PercentageFormatter(),
            'progresspercentage' => new PercentageFormatter(progressBar: true),
            'coloredfloat' => new FloatFormatter(colored: true),
            'state', 'badge' => new BadgeFormatter(),
            'email' => new LinkFormatter(type: 'email'),
            'tel' => new LinkFormatter(type: 'tel'),
            'url', 'link' => new LinkFormatter(),
            'relativetime' => new DateFormatter(mode: 'relative'),
            'time' => new DateFormatter(mode: 'time'),
            default => new StringFormatter(),
        };
    }
}
