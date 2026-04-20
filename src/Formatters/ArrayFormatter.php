<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class ArrayFormatter implements Formatter
{
    public function __construct(
        protected ?Formatter $elementFormatter = null,
    ) {}

    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        if (! is_array($value)) {
            return $this->elementFormatter
                ? $this->elementFormatter->format($value, $context)
                : e((string) $value);
        }

        if (empty($value)) {
            return '';
        }

        $value = array_filter($value, fn (mixed $item) => ! is_null($item));

        if (empty($value)) {
            return '';
        }

        $isFlat = array_reduce($value, fn (bool $carry, mixed $item) => $carry && is_scalar($item), true);

        if ($isFlat) {
            return implode(' ', array_map(
                function (mixed $item) use ($context): string {
                    $display = $this->elementFormatter
                        ? $this->elementFormatter->format($item, $context)
                        : e((string) $item);

                    return '<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">'
                        . $display . '</span>';
                },
                $value
            ));
        }

        return '<pre class="text-xs">' . e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    }
}
