@foreach ($this->enabledCols as $col)
    <div class="py-1">
        <x-checkbox
            sm
            value="{{ $col }}"
            x-model="exportColumns"
            :label="$this->colLabels[$col] ?? \Illuminate\Support\Str::headline($col)"
        />
    </div>
@endforeach
<div class="pt-3 border-t border-gray-200 dark:border-secondary-700 flex flex-col gap-3">
    <x-select.native
        x-model="exportFormat"
        :label="__('Format')"
        :options="[
            ['label' => 'Excel (.xlsx)', 'value' => 'xlsx'],
            ['label' => 'CSV (.csv)', 'value' => 'csv'],
            ['label' => 'JSON (.json)', 'value' => 'json'],
        ]"
    />
    <x-button
        loading
        x-on:click="$wire.export(exportColumns, exportFormat); $tsui.close.slide('data-table-sidebar-' + $wire.id.toLowerCase());"
        color="indigo"
        class="w-full"
        :text="__('Export')"
    />
</div>
