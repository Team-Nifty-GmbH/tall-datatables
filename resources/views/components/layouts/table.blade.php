<x-tall-datatables::table
    x-on:data-table-record-selected="if(! $wire.selected.includes('*')) return; ! $event.target.checked ? $wire.wildcardSelectExcluded.push($event.detail.{{ $selectValue }}) : console.log('adding')"
    class="relative"
>
    <tr
        wire:loading.delay.longer
        wire:target.except="storeColLayout"
        x-cloak
        class="absolute top-0 right-0 bottom-0 w-full"
    >
        <td>
            <x-tall-datatables::spinner />
        </td>
    </tr>
    @if ($hasHead)
        <x-slot:header>
            <tr>
                @if ($isSelectable)
                    <x-tall-datatables::table.head-cell
                        class="min-w-24 px-0! py-0!"
                    >
                        <div class="flex items-center justify-center gap-1.5">
                            @if ($selectValue === 'index')
                                <x-checkbox
                                    x-on:change="function (e) {
                                                if (e.target.checked) {
                                                        $wire.selected = Array.from(getData().keys());
                                                    $wire.selected.push('*');
                                                } else {
                                                    $wire.selected = [];
                                                    $wire.wildcardSelectExcluded = [];
                                                }
                                            }"
                                    value="*"
                                    wire:model="selected"
                                />
                            @else
                                <x-checkbox
                                    x-on:change="function (e) {
                                            if (e.target.checked) {
                                                $wire.selected = getData().map((record) => {{ $selectValue }});
                                                $wire.selected.push('*');
                                            } else {
                                                $wire.selected = [];
                                                $wire.wildcardSelectExcluded = [];
                                            }
                                        }"
                                    value="*"
                                    wire:model="selected"
                                />
                            @endif
                            <x-button
                                color="secondary"
                                light
                                class="px-1.5 py-1.5"
                                x-ref="selectedActions"
                                flat
                                icon="chevron-down"
                                x-on:click="$wire.selected.length > 0 ? showSelectedActions = true : null"
                            />
                        </div>
                        <div
                            x-on:click.outside="showSelectedActions = false"
                            x-transition:enter="transition duration-200 ease-out"
                            x-transition:enter-start="scale-95 opacity-0"
                            x-transition:enter-end="scale-100 opacity-100"
                            x-transition:leave="transition duration-75 ease-in"
                            x-transition:leave-start="scale-100 opacity-100"
                            x-transition:leave-end="scale-95 opacity-0"
                            class="z-30"
                            x-cloak
                            x-show="showSelectedActions"
                            x-anchor.bottom-start.offset.5="$refs.selectedActions"
                        >
                            <x-card x-on:click="showSelectedActions = false;">
                                <div class="flex flex-col gap-1.5">
                                    @foreach ($selectedActions as $action)
                                        {{ $action }}
                                    @endforeach
                                </div>
                            </x-card>
                        </div>
                    </x-tall-datatables::table.head-cell>
                @else
                    <th class="max-w-0"></th>
                @endif
                <template x-for="(col, index) in enabledCols">
                    <x-tall-datatables::table.head-cell
                        x-bind:class="stickyCols.includes(col) && 'left-0 z-10 border-r'"
                        x-bind:style="stickyCols.includes(col) && 'z-index: 2'"
                        :attributes="$tableHeadColAttributes"
                    >
                        <div class="flex">
                            <div
                                type="button"
                                wire:loading.attr="disabled"
                                class="group flex flex-row items-center space-x-1.5"
                                x-on:click="$wire.sortable.includes(col) && $wire.sortTable(col)"
                                x-bind:class="$wire.sortable.includes(col) ? 'cursor-pointer' : ''"
                            >
                                <span x-text="getLabel(col)"></span>
                                <x-icon
                                    x-bind:class="Object.keys($wire.sortable).length && orderByCol === col
                                    ? (orderAsc || 'rotate-180')
                                    : 'opacity-0'"
                                    name="chevron-up"
                                    class="h-4 w-4 transition-all"
                                />
                            </div>
                            @if ($hasStickyCols)
                                <div class="h-4 w-4">
                                    <svg
                                        x-bind:class="stickyCols.includes(col) ? 'fill-indigo-600' : ''"
                                        x-on:click="toggleStickyCol(col)"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="100%"
                                        height="100%"
                                        fill="currentColor"
                                        viewBox="0 0 256 256"
                                    >
                                        <path
                                            d="M235.32,81.37,174.63,20.69a16,16,0,0,0-22.63,0L98.37,74.49c-10.66-3.34-35-7.37-60.4,13.14a16,16,0,0,0-1.29,23.78L85,159.71,42.34,202.34a8,8,0,0,0,11.32,11.32L96.29,171l48.29,48.29A16,16,0,0,0,155.9,224c.38,0,.75,0,1.13,0a15.93,15.93,0,0,0,11.64-6.33c19.64-26.1,17.75-47.32,13.19-60L235.33,104A16,16,0,0,0,235.32,81.37ZM224,92.69h0l-57.27,57.46a8,8,0,0,0-1.49,9.22c9.46,18.93-1.8,38.59-9.34,48.62L48,100.08c12.08-9.74,23.64-12.31,32.48-12.31A40.13,40.13,0,0,1,96.81,91a8,8,0,0,0,9.25-1.51L163.32,32,224,92.68Z"
                                        ></path>
                                    </svg>
                                </div>
                            @endif

                            @if ($isFilterable && ! $showFilterInputs)
                                <x-icon
                                    name="funnel"
                                    x-show="filterable.includes(col)"
                                    class="h-4 w-4 cursor-pointer"
                                    x-on:click="loadSidebar({column: col, operator: '', value: '', relation: ''})"
                                />
                            @endif
                        </div>
                    </x-tall-datatables::table.head-cell>
                </template>
                @if ($rowActions ?? false)
                    <x-tall-datatables::table.head-cell class="w-[1%]">
                        {{ __('Actions') }}
                    </x-tall-datatables::table.head-cell>
                @endif

                @if ($hasSidebar)
                    <x-tall-datatables::table.head-cell
                        class="dark:bg-secondary-800 sticky right-0 flex w-4 flex-row-reverse bg-white py-0! shadow-inner"
                    >
                        <div class="flex w-full flex-row-reverse items-center">
                            <x-button
                                color="secondary"
                                light
                                icon="cog"
                                x-on:click="loadSidebar()"
                            />
                        </div>
                    </x-tall-datatables::table.head-cell>
                @endif
            </tr>
            @if ($isFilterable && $showFilterInputs)
                <tr>
                    <td class="dark:bg-secondary-600 max-w-0 bg-gray-50"></td>
                    <template x-for="(col, index) in enabledCols">
                        <td
                            class="dark:bg-secondary-600 bg-gray-50 px-2 py-1"
                            x-bind:style="stickyCols.includes(col) && 'z-index: 2'"
                            x-bind:class="stickyCols.includes(col) && 'sticky left-0 border-r'"
                        >
                            <template
                                x-if="! filterValueLists.hasOwnProperty(col)"
                            >
                                <div
                                    x-cloak
                                    x-show="filterable.includes(col)"
                                >
                                    <x-input
                                        type="search"
                                        class="p-1"
                                        x-model.debounce.500ms="textFilter[col]"
                                    />
                                </div>
                            </template>
                            <template
                                x-if="filterValueLists.hasOwnProperty(col)"
                            >
                                <x-select.native
                                    x-model="textFilter[col]"
                                    placeholder="{{ __('Value') }}"
                                >
                                    <option value=""></option>
                                    <template
                                        x-for="item in filterValueLists[col]"
                                    >
                                        <option
                                            x-bind:value="item.value"
                                            x-text="item.label"
                                        ></option>
                                    </template>
                                </x-select.native>
                            </template>
                        </td>
                    </template>
                    @if ($rowActions ?? false)
                        <td class="dark:bg-secondary-800 bg-gray-50"></td>
                    @endif

                    @if ($hasSidebar)
                        <td
                            class="dark:bg-secondary-800 sticky right-0 bg-gray-50"
                        ></td>
                    @endif
                </tr>
            @endif
        </x-slot>
    @endif

    <template x-if="! getData().length && initialized">
        <tr>
            <td colspan="100%" class="h-24 w-24 p-8">
                <div class="w-full flex-col items-center dark:text-gray-50">
                    <x-icon
                        outline
                        name="face-frown"
                        class="m-auto h-24 w-24"
                    />
                    <div class="text-center">
                        {{ __('No data found') }}
                    </div>
                </div>
            </td>
        </tr>
    </template>
    <tr x-show="! initialized">
        <td colspan="100%" class="h-24 w-24 p-8"></td>
    </tr>
    <template
        x-for="(record, index) in getData()"
        :key="{{ $selectValue }}"
    >
        <tr
            x-bind:data-id="record.id"
            x-bind:key="record.id"
            x-on:click="$dispatch('data-table-row-clicked', {record: record})"
            @if($allowSoftDeletes) x-bind:class="record.deleted_at ? 'opacity-50' : ''" @endif
            {{ $rowAttributes->merge(['class' => 'hover:bg-gray-100 dark:hover:bg-secondary-900']) }}
        >
            @if ($isSelectable)
                <td
                    class="border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap dark:border-slate-600"
                >
                    <div
                        {{ $selectAttributes->merge(['class' => 'flex justify-center']) }}
                    >
                        <x-checkbox
                            x-on:click="$event.stopPropagation();"
                            x-on:change="$dispatch('data-table-record-selected', {record: record, index: index, value: $el.checked});"
                            x-bind:value="{{ $selectValue }}"
                            x-model.number="$wire.selected"
                        />
                    </div>
                </td>
            @else
                <td
                    class="max-w-0 border-b border-slate-200 text-sm whitespace-nowrap dark:border-slate-600"
                ></td>
            @endif
            <template x-for="col in enabledCols">
                <x-tall-datatables::table.cell
                    :use-wire-navigate="$useWireNavigate"
                    x-bind:class="stickyCols.includes(col) && 'sticky left-0 border-r bg-white dark:bg-secondary-800 dark:text-gray-50'"
                    x-bind:style="stickyCols.includes(col) && 'z-index: 2'"
                    class="cursor-pointer"
                    x-bind:href="record.deleted_at ? false : (record?.href ?? false)"
                >
                    <div class="flex flex-wrap gap-1.5">
                        <div
                            class="flex flex-wrap gap-1"
                            x-cloak
                            x-show="leftAppend[col]"
                            x-html="formatter(leftAppend[col], record)"
                        ></div>
                        <div class="grow">
                            <div
                                class="flex flex-wrap gap-1"
                                x-cloak
                                x-show="topAppend[col]"
                                x-html="formatter(topAppend[col], record)"
                            ></div>
                            <div
                                class="flex flex-wrap gap-1"
                                {{ $cellAttributes->merge(['x-html' => 'formatter(col, record)']) }}
                            ></div>
                            <div
                                class="flex flex-wrap gap-1"
                                x-cloak
                                x-show="bottomAppend[col]"
                                x-html="formatter(bottomAppend[col], record)"
                            ></div>
                        </div>
                        <div
                            class="flex flex-wrap gap-1"
                            x-cloak
                            x-show="rightAppend[col]"
                            x-html="formatter(rightAppend[col], record)"
                        ></div>
                    </div>
                </x-tall-datatables::table.cell>
            </template>
            @if (($rowActions ?? false) || ($showRestoreButton && $allowSoftDeletes))
                <td
                    x-on:click.stop
                    class="border-b border-slate-200 px-3 py-4 whitespace-nowrap dark:border-slate-600"
                >
                    <div
                        class="flex gap-1.5"
                        @if($allowSoftDeletes) x-bind:class="record.deleted_at ? 'hidden' : ''" @endif
                    >
                        @foreach ($rowActions ?? [] as $rowAction)
                            {{ $rowAction }}
                        @endforeach
                    </div>
                    @if ($showRestoreButton && $allowSoftDeletes)
                        <div class="flex gap-1.5" x-show="record.deleted_at">
                            <x-button
                                color="indigo"
                                wire:click="restore(record.id)"
                            >
                                {{ __('Restore') }}
                            </x-button>
                        </div>
                    @endif
                </td>
            @endif

            {{-- Empty cell for the col selection --}}
            @if ($hasSidebar)
                <td
                    class="sticky right-0 table-cell border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap shadow-sm dark:border-slate-600"
                ></td>
            @endif
        </tr>
    </template>
    <x-slot:footer>
        <template x-for="(aggregate, name) in data.aggregates">
            <tr
                class="dark:hover:bg-secondary-800 dark:bg-secondary-900 bg-gray-50 hover:bg-gray-100"
            >
                <td
                    class="border-b border-slate-200 px-3 py-4 text-sm font-bold whitespace-nowrap dark:border-slate-600"
                    x-text="getLabel(name)"
                ></td>
                <template x-for="col in enabledCols">
                    <x-tall-datatables::table.cell>
                        <div
                            class="flex font-semibold"
                            x-html="formatter(col, aggregate)"
                        ></div>
                    </x-tall-datatables::table.cell>
                </template>
                <td
                    class="table-cell border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap dark:border-slate-600"
                ></td>
                @if ($rowActions)
                    <td
                        class="table-cell border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap dark:border-slate-600"
                    ></td>
                @endif
            </tr>
        </template>
        @if (! $hasInfiniteScroll)
            <template x-if="data.hasOwnProperty('current_page')">
                <tr>
                    <td colspan="100%">
                        <x-tall-datatables::pagination />
                    </td>
                </tr>
            </template>
        @else
            <tr>
                <td
                    x-intersect:enter="$wire.get('initialized') && $wire.loadMore()"
                    colspan="100%"
                >
                    <x-button
                        color="secondary"
                        light
                        flat
                        loading="loadMore"
                        delay="longer"
                        class="w-full"
                    >
                        {{ __('Loading...') }}
                    </x-button>
                </td>
            </tr>
        @endif
    </x-slot>
</x-tall-datatables::table>
