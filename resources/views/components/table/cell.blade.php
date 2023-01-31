<a {{ $attributes->merge(['class' => 'table-cell border-b border-slate-200 dark:border-slate-600 whitespace-nowrap px-3 py-4 text-sm']) }} >
    {{ $slot ?? '' }}
</a>
