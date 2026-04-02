<div class="mb-3 pb-3 border-b border-gray-200 dark:border-secondary-700" x-show="groupBy" x-cloak>
    <span class="mb-2 block text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400">{{ __('Rows per group') }}</span>
    <x-select.native
        sm
        x-model="$wire.groupPerPage"
        x-on:change="$wire.loadData()"
    >
        <option value="5">5</option>
        <option value="10">10</option>
        <option value="15">15</option>
        <option value="25">25</option>
        <option value="50">50</option>
    </x-select.native>
</div>
<div class="pb-2">
    <x-input sm
        type="search"
        x-model.debounce.300ms="searchGroupable"
        placeholder="{{ __('Search') }}"
        class="w-full"
    />
</div>
<div class="space-y-2">
    <div
        class="flex items-center justify-between rounded-lg border p-2.5 text-sm"
        x-bind:class="! groupBy ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-secondary-700'"
    >
        <x-radio
            name="groupBy"
            :label="__('No grouping')"
            value=""
            x-bind:checked="! groupBy"
            x-on:change="groupBy = null; $wire.setGroupBy(null)"
        />
        <x-icon
            name="view-columns"
            class="h-5 w-5 text-gray-400"
            x-bind:class="! groupBy && 'text-primary-500'"
        />
    </div>
    <template x-for="col in searchable(groupable, searchGroupable)">
        <x-radio
            name="groupBy"
            x-bind:value="col"
            x-bind:checked="groupBy === col"
            x-on:change="groupBy = col; $wire.setGroupBy(col)"
        >
            <x-slot:label>
                <span x-text="getLabel(col)"></span>
            </x-slot:label>
        </x-radio>
    </template>
</div>
