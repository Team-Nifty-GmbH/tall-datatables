<?php

namespace TeamNiftyGmbH\DataTable\Formatters;

use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;

class BadgeFormatter implements Formatter
{
    /**
     * @param  array<string, array{color?: string, label?: string}>  $mapping
     */
    public function __construct(public readonly array $mapping = []) {}

    public function format(mixed $value, array $context = []): string
    {
        if (is_null($value)) {
            return '';
        }

        $key = (string) $value;

        if (array_key_exists($key, $this->mapping)) {
            $config = $this->mapping[$key];
            $color = $config['color'] ?? 'gray';
            $label = $config['label'] ?? $key;

            return $this->renderBadge($color, e($label));
        }

        return $this->renderBadge('gray', e($key));
    }

    protected function renderBadge(string $color, string $label): string
    {
        $colorClasses = match ($color) {
            'red' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
            'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-200',
            'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
            'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200',
            'lime' => 'bg-lime-100 text-lime-800 dark:bg-lime-900/50 dark:text-lime-200',
            'green' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200',
            'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
            'teal' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/50 dark:text-teal-200',
            'cyan' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-200',
            'sky' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200',
            'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
            'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-200',
            'violet' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-200',
            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-200',
            'fuchsia' => 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-900/50 dark:text-fuchsia-200',
            'pink' => 'bg-pink-100 text-pink-800 dark:bg-pink-900/50 dark:text-pink-200',
            'rose' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-200',
        };

        return '<span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ' . $colorClasses . '">' . $label . '</span>';
    }
}
