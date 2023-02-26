<div
    wire:init="loadData()"
    x-data
    x-id="['save-filter', 'cols', 'operators', 'filter-select-search', 'table-cols']"
>
    <div
        class="relative"
        wire:ignore
        x-data="data_table($wire)"
        {{ $attributes }}
    >
        {{ $slot }}
    </div>
</div>
