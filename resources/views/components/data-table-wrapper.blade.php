<div x-init.once="$wire.loadData()">
    <div
        class="relative"
        tall-datatable
        x-data="data_table($wire)"
        {{ $attributes }}
        x-id="['save-filter', 'enabledCols', 'operators', 'filter-select-search', 'table-cols']"
    >
        {{ $slot }}
    </div>
</div>
