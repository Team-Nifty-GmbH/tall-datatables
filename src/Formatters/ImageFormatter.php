<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class ImageFormatter implements Formatter
{
    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        // Handle Image cast objects with src, alt, title, class properties
        if (is_object($value) && property_exists($value, 'src')) {
            $src = e($value->src ?? '');
            $alt = isset($value->alt) ? e($value->alt) : '';
            $title = isset($value->title) ? ' title="' . e($value->title) . '"' : '';
            $class = isset($value->class) ? e($value->class) : 'h-8 w-8 object-cover rounded';

            if (! $src) {
                return '';
            }

            return '<img src="' . $src . '" alt="' . $alt . '"' . $title . ' class="' . $class . '" />';
        }

        // Plain URL string
        $src = e((string) $value);

        if (! $src) {
            return '';
        }

        return '<img src="' . $src . '" alt="" class="h-8 w-8 object-cover rounded" />';
    }
}
