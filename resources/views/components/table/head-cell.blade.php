@props(['wrap' => false])
{{--
    A column is otherwise always at least as wide as its own heading: the
    automatic layout takes the widest thing in a column, and a heading on one
    line is unbreakable. A column labelled "Oligo Purification Method" holding
    "HPLC" then takes three times the room it needs, which is what limits how
    much of a table fits on screen.

    Letting the heading wrap stays off by default, because it changes how every
    table looks. A data table turns it on with $wrapColumnLabels.
--}}
<th
    {{ $attributes->merge(['style' => 'z-index: 1', 'class' => 'relative table-cell ' . ($wrap ? '' : 'whitespace-nowrap ') . 'px-3 py-2.5 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-secondary-800 sticky top-0 border-b border-gray-200 dark:border-secondary-700/50']) }}
>
    {{ $slot ?? '' }}
</th>
