<th
    {{ $attributes->merge(['style' => 'z-index: 1', 'class' => 'table-cell whitespace-nowrap px-3 py-2.5 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-secondary-800 sticky top-0 border-b border-gray-200 dark:border-secondary-700/50']) }}
>
    {{ $slot ?? '' }}
</th>
