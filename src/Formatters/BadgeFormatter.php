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
            'red' => 'bg-red-100 text-red-800',
            'orange' => 'bg-orange-100 text-orange-800',
            'amber' => 'bg-amber-100 text-amber-800',
            'yellow' => 'bg-yellow-100 text-yellow-800',
            'lime' => 'bg-lime-100 text-lime-800',
            'green' => 'bg-green-100 text-green-800',
            'emerald' => 'bg-emerald-100 text-emerald-800',
            'teal' => 'bg-teal-100 text-teal-800',
            'cyan' => 'bg-cyan-100 text-cyan-800',
            'sky' => 'bg-sky-100 text-sky-800',
            'blue' => 'bg-blue-100 text-blue-800',
            'indigo' => 'bg-indigo-100 text-indigo-800',
            'violet' => 'bg-violet-100 text-violet-800',
            'purple' => 'bg-purple-100 text-purple-800',
            'fuchsia' => 'bg-fuchsia-100 text-fuchsia-800',
            'pink' => 'bg-pink-100 text-pink-800',
            'rose' => 'bg-rose-100 text-rose-800',
            default => 'bg-gray-100 text-gray-800',
        };

        return '<span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ' . $colorClasses . '">' . $label . '</span>';
    }
}
