<th
    {{ $attributes->merge(['style' => 'z-index: 1', 'class' => 'table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-50 bg-white dark:bg-secondary-800 sticky top-0 font-semibold']) }}
>
    {{ $slot ?? '' }}
</th>
