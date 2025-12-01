@props([
    'isSelectable' => false,
    'selectValue' => 'record.id',
    'selectAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'rowAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'cellAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'allowSoftDeletes' => false,
    'useWireNavigate' => true,
    'rowActions' => [],
    'showRestoreButton' => false,
    'hasSidebar' => true,
])
<template x-for="row in getFlatGroupedRows()" x-bind:key="row._key">
    <tr
        x-bind:data-row-type="row.rowType"
        x-bind:data-id="row.record?.id"
        x-bind:class="{
            'dark:bg-secondary-700 cursor-pointer bg-gray-100 hover:bg-gray-200 dark:hover:bg-secondary-600': row.rowType === 'group-header',
            'hover:bg-gray-100 dark:hover:bg-secondary-900': row.rowType === 'data',
            'dark:bg-secondary-800 bg-gray-50': row.rowType === 'pagination',
            'dark:bg-secondary-800 bg-gray-100': row.rowType === 'groups-pagination',
            'opacity-50': row.rowType === 'data' && row.record?.deleted_at
        }"
        x-on:click="row.rowType === 'group-header' ? toggleGroup(row.group.key) : (row.rowType === 'data' ? $dispatch('data-table-row-clicked', {record: row.record}) : null)"
        x-html="renderGroupedRow(row)"
    >
    </tr>
</template>
