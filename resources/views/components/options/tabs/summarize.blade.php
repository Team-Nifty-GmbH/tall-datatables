<div class="pb-2">
    <x-input sm
        type="search"
        x-model.debounce.300ms="searchAggregatable"
        placeholder="{{ __('Search') }}"
        class="w-full"
    />
</div>
<div class="grid grid-cols-1 gap-2">
    <template
        x-for="col in searchable(aggregatable, searchAggregatable)"
    >
        <div class="pt-3 border-t border-gray-200 dark:border-secondary-700">
            <span class="mb-1 block text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400" x-text="getLabel(col)"></span>
            <div class="py-1">
                <x-checkbox
                    sm
                    :label="__('Sum')"
                    x-bind:value="col"
                    x-model="aggregatableCols.sum"
                />
            </div>
            <div class="py-1">
                <x-checkbox
                    sm
                    :label="__('Average')"
                    x-bind:value="col"
                    x-model="aggregatableCols.avg"
                />
            </div>
            <div class="py-1">
                <x-checkbox
                    sm
                    :label="__('Minimum')"
                    x-bind:value="col"
                    x-model="aggregatableCols.min"
                />
            </div>
            <div class="py-1">
                <x-checkbox
                    sm
                    :label="__('Maximum')"
                    x-bind:value="col"
                    x-model="aggregatableCols.max"
                />
            </div>
            <div class="py-1">
                <x-checkbox
                    sm
                    :label="__('Count')"
                    x-bind:value="col"
                    x-model="aggregatableCols.count"
                />
            </div>
        </div>
    </template>
</div>
