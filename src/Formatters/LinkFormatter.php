<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class LinkFormatter implements Formatter
{
    public function __construct(public readonly string $type = 'link') {}

    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        // Handle Link cast objects with url, label, target properties
        if (is_object($value) && property_exists($value, 'url')) {
            $url = e($value->url ?? '');
            $label = e($value->label ?? $value->url ?? '');
            $target = isset($value->target) ? ' target="' . e($value->target) . '"' : '';

            if (! $url) {
                return $label;
            }

            return '<a href="' . $url . '"' . $target . ' class="text-blue-600 hover:text-blue-800 underline">' . $label . '</a>';
        }

        $stringValue = (string) $value;

        return match ($this->type) {
            'email' => '<a href="mailto:' . e($stringValue) . '" class="text-blue-600 hover:text-blue-800 underline">' . e($stringValue) . '</a>',
            'tel' => '<a href="tel:' . e($stringValue) . '" class="text-blue-600 hover:text-blue-800 underline">' . e($stringValue) . '</a>',
            'url' => '<a href="' . e($stringValue) . '" class="text-blue-600 hover:text-blue-800 underline">' . e($stringValue) . '</a>',
            default => '<a href="' . e($stringValue) . '" class="text-blue-600 hover:text-blue-800 underline">' . e($stringValue) . '</a>',
        };
    }
}
