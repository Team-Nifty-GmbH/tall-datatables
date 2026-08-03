{{--
    The label is allowed to wrap. Without that a column was always at least as
    wide as its own heading: the automatic layout takes the widest thing in the
    column, and a heading on one line is unbreakable. A column labelled "Oligo
    Purification Method" therefore took three times the room its "HPLC" needed,
    which is what limited how much of a table fits on screen.

    A cell that wants to stay on one line, the score or the actions, says so
    itself and keeps winning, because those classes are merged in front.
--}}
<th
    {{ $attributes->merge(['style' => 'z-index: 1', 'class' => 'relative table-cell px-3 py-2.5 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-secondary-800 sticky top-0 border-b border-gray-200 dark:border-secondary-700/50']) }}
>
    {{ $slot ?? '' }}
</th>
