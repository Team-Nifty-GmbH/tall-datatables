<div
    x-data="{
        attributes: [],
        availableCols: [...$wire.enabledCols, ...['__placeholder__']],
        addCol(colName) {
            if (this.availableCols.includes(colName))
                this.availableCols.splice(this.availableCols.indexOf(colName), 1)
            else {
                this.availableCols.push(colName)
            }
        },
    }"
>
    <div class="border-b border-gray-200 pb-2 dark:border-secondary-700">
    <div
        class="table-cols"
        x-sort="columnSortHandle($item, $position)"
    >
        <template x-for="col in availableCols">
            <div
                x-sort:item="col"
                x-bind:data-column="col"
                x-cloak
                x-show="col !== '__placeholder__'"
                class="min-w-0 py-1"
            >
                <label
                    x-bind:for="col"
                    class="flex min-w-0 items-center"
                >
                    <div class="relative flex min-w-0 items-start">
                        <div class="flex h-5 min-w-0 items-center">
                            <x-checkbox
                                sm
                                x-bind:id="col"
                                x-bind:value="col"
                                x-model="enabledCols"
                                wire:loading.attr="disabled"
                            >
                                <x-slot:label>
                                    <span
                                        class="block truncate text-sm text-gray-700 dark:text-gray-300"
                                        x-text="getLabel(col)"
                                    />
                                </x-slot>
                            </x-checkbox>
                        </div>
                    </div>
                </label>
            </div>
        </template>
    </div>
    <div class="flex justify-start pt-2">
        <x-button
            color="secondary"
            light
            loading="resetLayout"
            x-on:click="resetLayout"
            :text="__('Reset Layout')"
        />
    </div>
    </div>
    <div
        class="pt-3 text-sm font-medium text-gray-700 dark:text-gray-50"
    >
        <nav class="flex items-center overflow-x-auto" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1 text-sm">
                <li>
                    <x-button
                        xs
                        flat
                        color="primary"
                        :text="__('This table')"
                        x-on:click="
                            searchRelations = null;
                            searchColumns = null;
                            (async () => {
                                const d = await $wire.loadSlug();
                                selectedCols = d?.cols || [];
                                selectedRelations = d?.relations || [];
                                displayPath = d?.displayPath || [];
                            })();
                        "
                    />
                </li>
                <template x-for="segment in displayPath">
                    <li class="flex items-center gap-1">
                        <x-icon name="chevron-right" class="h-3 w-3 text-gray-400" />
                        <x-button
                            xs
                            flat
                            color="primary"
                            x-on:click="
                                searchRelations = null;
                                searchColumns = null;
                                (async () => {
                                    const d = await $wire.loadSlug(segment.value);
                                    selectedCols = d?.cols || [];
                                    selectedRelations = d?.relations || [];
                                    displayPath = d?.displayPath || [];
                                })();
                            "
                        >
                            <span x-text="segment.label"></span>
                        </x-button>
                    </li>
                </template>
            </ol>
        </nav>
        <hr class="mt-2 border-gray-200 pb-2.5 dark:border-secondary-700" />
        <div class="grid grid-cols-2 gap-1.5 overflow-hidden">
            <div class="min-w-0 overflow-hidden">
                <span class="mb-1 block text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400">{{ __('Columns') }}</span>
                <div class="pb-2">
                    <x-input sm
                        type="search"
                        x-model.debounce.300ms="searchColumns"
                        placeholder="{{ __('Search') }}"
                        class="w-full"
                    />
                </div>
                <template
                    x-for="col in searchable(selectedCols, searchColumns)"
                >
                    <label class="flex min-w-0 cursor-pointer items-center gap-1.5 overflow-hidden py-1">
                        <x-checkbox
                            sm
                            x-bind:checked="$wire.enabledCols.includes(col.attribute)"
                            wire:loading.attr="disabled"
                            x-bind:id="col.attribute"
                            x-bind:value="col.attribute"
                            x-on:change="loadFilterable(); addCol(col.attribute);"
                            x-model="enabledCols"
                        />
                        <span
                            class="truncate text-sm text-gray-700 dark:text-gray-300"
                            x-text="col.label"
                        ></span>
                    </label>
                </template>
            </div>
            <div class="min-w-0 overflow-hidden">
                <span class="mb-1 block text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400">{{ __('Relations') }}</span>
                <div class="pb-2">
                    <x-input sm
                        type="search"
                        x-model.debounce.300ms="searchRelations"
                        placeholder="{{ __('Search') }}"
                        class="w-full"
                    />
                </div>
                <template
                    x-for="relation in searchable(selectedRelations, searchRelations)"
                >
                    <div
                        class="flex min-w-0 cursor-pointer items-center gap-1.5 overflow-hidden py-1"
                        x-on:click="
                            searchRelations = null;
                            searchColumns = null;
                            (async () => {
                                const data = await $wire.loadRelation(relation.model, relation.name);
                                selectedCols = data?.cols || [];
                                selectedRelations = data?.relations || [];
                                displayPath = data?.displayPath || [];
                            })();
                        "
                    >
                        <div x-on:click.stop x-show="displayPath.length === 0" x-cloak>
                            <x-checkbox
                                sm
                                x-bind:value="relation.name + '_count'"
                                x-bind:checked="$wire.enabledCols.includes(relation.name + '_count')"
                                x-on:change="addCol(relation.name + '_count'); loadFilterable();"
                                x-model="enabledCols"
                                wire:loading.attr="disabled"
                            />
                        </div>
                        <span
                            class="truncate text-sm text-gray-700 dark:text-gray-300"
                            x-text="relation.label"
                        ></span>
                        <x-icon
                            name="chevron-right"
                            class="h-4 w-4 shrink-0"
                        />
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
