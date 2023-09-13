@props([
    'useWireNavigate' => false,
])
<a {{ $useWireNavigate ? 'wire:navigate' : '' }} {{ $attributes->merge(['class' => 'align-top table-cell border-b border-slate-200 dark:border-slate-600 whitespace-normal px-3 py-4 text-sm bg-white dark:bg-secondary-800']) }} >
    {{ $slot ?? '' }}
</a>
