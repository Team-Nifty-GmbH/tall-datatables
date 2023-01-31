<td {{ $attributes->merge(['class' => 'table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-50']) }} >
    {{ $slot ?? '' }}
</td>
