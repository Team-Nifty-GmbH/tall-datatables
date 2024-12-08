<div wire:init="loadData()">
    <div
        class="relative"
        wire:ignore
        tall-datatable
        x-data="data_table($wire)"
        {{ $attributes }}
        x-id="['save-filter', 'enabledCols', 'operators', 'filter-select-search', 'table-cols']"
    >
        {{ $slot }}
    </div>
</div>
