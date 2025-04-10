<div x-data="{ showButtons: null }">
    @if ($isFilterable)
        <div
            class="flex w-full flex-row-reverse items-center justify-between pt-2"
        >
            <x-button
                color="secondary"
                light
                icon="cog"
                x-on:click="loadSidebar()"
            />
            <template x-if="sortable">
                <x-dropdown align="left" persistent>
                    <x-slot:action>
                        <x-button
                            color="secondary"
                            light
                            :text="__('Sort')"
                            icon="adjustments-vertical"
                        />
                    </x-slot>
                    <template
                        x-for="sortableItem in sortable.filter((item) => item !== '*')"
                    >
                        <a
                            class="text-secondary-600 hover:text-secondary-900 hover:bg-secondary-100 dark:text-secondary-400 dark:hover:bg-secondary-700 flex cursor-pointer items-center justify-between rounded-md px-4 py-2 text-sm transition-colors duration-150"
                            x-on:click="$wire.sortTable(sortableItem)"
                        >
                            <div x-text="colLabels[sortableItem]"></div>
                            <x-icon
                                x-bind:class="Object.keys(sortable).length && orderByCol === sortableItem
                                ? (orderAsc || 'rotate-180')
                                : 'opacity-0'"
                                name="chevron-down"
                                class="h-4 w-4 transition-all"
                            />
                        </a>
                    </template>
                </x-dropdown>
            </template>
        </div>
    @endif

    <template x-if="! getData().length && initialized">
        <div class="h-24 w-24 w-full p-8">
            <div class="w-full flex-col items-center dark:text-gray-50">
                <x-icon name="face-frown" class="m-auto h-24 w-24" />
                <div class="text-center">
                    {{ __('No data found') }}
                </div>
            </div>
        </div>
    </template>
    <div
        class="mt-8 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 md:grid-cols-4 xl:grid-cols-6 xl:gap-x-8 2xl:grid-cols-8"
    >
        <div
            wire:loading.delay.longer
            wire:target.except="storeColLayout"
            x-cloak
            class="absolute top-0 right-0 bottom-0 w-full"
        >
            <x-tall-datatables::spinner />
        </div>
        <template x-for="(record, index) in getData()">
            <div
                x-bind:data-id="record.id"
                x-bind:key="record.id"
                x-on:click="$dispatch('data-table-row-clicked', record)"
                {{ $rowAttributes->merge(['class' => 'hover:bg-gray-100 dark:hover:bg-secondary-900 rounded-md pb-1.5']) }}
            >
                <a
                    class="relative text-sm font-medium text-gray-500 dark:text-gray-50"
                    x-bind:href="record?.href ?? false"
                >
                    <template x-for="(col, index) in enabledCols">
                        <div>
                            <template x-if="formatters[col] === 'image'">
                                <div
                                    class="relative h-72 w-full overflow-hidden rounded-lg"
                                >
                                    @if ($rowActions)
                                        <div class="absolute top-2 right-2">
                                            <x-dropdown
                                                icon="ellipsis-vertical"
                                                static
                                            >
                                                <div
                                                    class="grid grid-cols-1 gap-1.5"
                                                >
                                                    @foreach ($rowActions as $rowAction)
                                                        {{ $rowAction }}
                                                    @endforeach
                                                </div>
                                            </x-dropdown>
                                        </div>
                                    @endif

                                    <img
                                        x-bind:src="record[col]"
                                        class="h-full w-full object-cover object-center"
                                    />
                                </div>
                            </template>
                            <template x-if="formatters[col] !== 'image'">
                                <div
                                    class="mt-4 flex px-2"
                                    x-bind:class="index === 1 && 'font-semibold'"
                                >
                                    <div
                                        class="flex flex-wrap gap-1"
                                        x-html="formatter(leftAppend[col], record)"
                                    ></div>
                                    <div>
                                        <div
                                            class="flex flex-wrap gap-1"
                                            x-html="formatter(topAppend[col], record)"
                                        ></div>
                                        <div
                                            class="flex flex-wrap gap-1"
                                            x-html="formatter(col, record)"
                                        ></div>
                                        <div
                                            x-html="formatter(bottomAppend[col], record)"
                                        ></div>
                                    </div>
                                    <div
                                        class="flex flex-wrap gap-1"
                                        x-html="formatter(rightAppend[col], record)"
                                    ></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </a>
            </div>
        </template>
    </div>
    @if (! $hasInfiniteScroll)
        <template x-if="data.hasOwnProperty('current_page')">
            <div class="w-full">
                <x-tall-datatables::pagination />
            </div>
        </template>
    @else
        <div
            x-intersect:enter="$wire.get('initialized') && $wire.loadMore()"
            class="w-full"
        >
            <x-button
                color="secondary"
                light
                flat
                delay="longer"
                loading="loadMore"
                class="w-full"
            >
                {{ __('Loading...') }}
            </x-button>
        </div>
    @endif
</div>
