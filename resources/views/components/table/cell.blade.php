<a {{ $attributes->merge(['class' => 'align-top table-cell border-b border-slate-200 dark:border-slate-600 whitespace-normal px-3 py-4 text-sm']) }} >
    {{ $slot ?? '' }}
</a>
