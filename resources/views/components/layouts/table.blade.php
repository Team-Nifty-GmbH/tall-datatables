<x-tall-datatables::table class="relative">
    <tr wire:loading.delay.longer class="absolute bottom-0 top-0 right-0 w-full">
        <td>
            <x-tall-datatables::spinner />
        </td>
    </tr>
    @if($hasHead)
        <x-slot:header>
            <tr>
                <x-tall-datatables::table.head-cell>
                    <template x-if="selectable">
                        <x-checkbox x-on:change="function (e) {
                            if (e.target.checked) {
                                selected = getData().map(record => record.id);
                                selected.push('*');
                            } else {
                                selected = [];
                            }
                        }" value="*" x-model="selected"/>
                    </template>
                </x-tall-datatables::table.head-cell>
                <template x-for="(col, index) in enabledCols">
                    <x-tall-datatables::table.head-cell
                        x-bind:class="stickyCols.includes(col) && 'left-0 z-10 border-r'"
                        x-bind:style="stickyCols.includes(col) && 'z-index: 2'"
                        :attributes="$tableHeadColAttributes">
                        <div class="flex">
                            <div
                                type="button"
                                wire:loading.attr="disabled"
                                class="flex flex-row items-center space-x-1.5 group"
                                x-on:click="sortable.includes(col) && $wire.sortTable(col)"
                                x-bind:class="sortable.includes(col) ? 'cursor-pointer' : ''"
                            >
                                <span x-text="getLabel(col)"></span>
                                <x-icon
                                    x-bind:class="Object.keys(sortable).length && orderByCol === col
                                    ? (orderAsc || 'rotate-180')
                                    : 'opacity-0'"
                                    name="chevron-up"
                                    class="h-4 w-4 transition-all"
                                />
                            </div>
                            @if($hasStickyCols)
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
                                        <path d="M235.32,81.37,174.63,20.69a16,16,0,0,0-22.63,0L98.37,74.49c-10.66-3.34-35-7.37-60.4,13.14a16,16,0,0,0-1.29,23.78L85,159.71,42.34,202.34a8,8,0,0,0,11.32,11.32L96.29,171l48.29,48.29A16,16,0,0,0,155.9,224c.38,0,.75,0,1.13,0a15.93,15.93,0,0,0,11.64-6.33c19.64-26.1,17.75-47.32,13.19-60L235.33,104A16,16,0,0,0,235.32,81.37ZM224,92.69h0l-57.27,57.46a8,8,0,0,0-1.49,9.22c9.46,18.93-1.8,38.59-9.34,48.62L48,100.08c12.08-9.74,23.64-12.31,32.48-12.31A40.13,40.13,0,0,1,96.81,91a8,8,0,0,0,9.25-1.51L163.32,32,224,92.68Z">
                                        </path>
                                    </svg>
                                </div>
                            @endif
                            @if($isFilterable && ! $showFilterInputs)
                                <x-icon name="filter"
                                    x-show="filterable.includes(col)"
                                    class="h-4 w-4 cursor-pointer"
                                    x-on:click="loadSidebar({column: col, operator: '', value: '', relation: ''})"
                                />
                            @endif
                        </div>
                    </x-tall-datatables::table.head-cell>
                </template>
                @if($rowActions ?? false)
                    <x-tall-datatables::table.head-cell class="w-[1%]">
                        {{ __('Actions') }}
                    </x-tall-datatables::table.head-cell>
                @endif
                @if($hasSidebar)
                    <x-tall-datatables::table.head-cell class="!py-0 w-4 flex flex-row-reverse bg-white dark:bg-secondary-800 sticky right-0 shadow-inner">
                        <div class="flex w-full flex-row-reverse items-center">
                            <x-button
                                icon="cog"
                                x-on:click="loadSidebar()"
                            />
                        </div>
                    </x-tall-datatables::table.head-cell>
                @endif
            </tr>
            @if($isFilterable && $showFilterInputs)
                <tr>
                    <td class="bg-gray-50 dark:bg-secondary-600"></td>
                    <template x-for="(col, index) in enabledCols">
                        <td class="bg-gray-50 dark:bg-secondary-600 py-1 px-2"
                            x-bind:style="stickyCols.includes(col) && 'z-index: 2'"
                            x-bind:class="stickyCols.includes(col) && 'sticky left-0 border-r'">
                            <template x-if="! filterValueLists.hasOwnProperty(col)">
                                <x-input class="p-1" x-model.debounce.500ms="textFilter[col]" x-show="filterable.includes(col)" />
                            </template>
                            <template x-if="filterValueLists.hasOwnProperty(col)">
                                <x-native-select
                                    x-model="textFilter[col]"
                                    placeholder="{{ __('Value') }}"
                                >
                                    <option value=""></option>
                                    <template x-for="item in filterValueLists[col]">
                                        <option x-bind:value="item.value" x-text="item.label"></option>
                                    </template>
                                </x-native-select>
                            </template>
                        </td>
                    </template>
                    @if($rowActions ?? false)
                        <td>
                        </td>
                    @endif
                    @if($hasSidebar)
                        <td class="bg-gray-50 dark:bg-secondary-800 sticky right-0"></td>
                    @endif
                </tr>
            @endif
        </x-slot:header>
    @endif
    <template x-if="! getData().length && initialized">
        <tr>
            <td colspan="100%" class="p-8 w-24 h-24">
                <div class="w-full flex-col items-center dark:text-gray-50">
                    <x-icon
                        name="emoji-sad"
                        class="h-24 w-24 m-auto"
                    />
                    <div class="text-center">
                        {{ __('No data found') }}
                    </div>
                </div>
            </td>
        </tr>
    </template>
    <tr x-show="! initialized">
        <td colspan="100%" class="p-8 w-24 h-24">
        </td>
    </tr>
    <template x-for="(record, index) in getData()">
        <tr
            x-bind:data-id="record.id"
            x-bind:key="record.id"
            x-on:click="$dispatch('data-table-row-clicked', record)"
            {{ $rowAttributes->merge(['class' => 'hover:bg-gray-100 dark:hover:bg-secondary-900']) }}
        >
            <td class="border-b border-slate-200 dark:border-slate-600 whitespace-nowrap px-3 py-4 text-sm">
                <template x-if="selectable">
                    <div {{ $selectAttributes }}>
                        <x-checkbox
                            x-on:click="$event.stopPropagation();"
                            x-on:change="$dispatch('data-table-record-selected', {record: record, index: index, value: $el.checked});"
                            x-bind:value="record.id"
                            x-model="selected"
                        />
                    </div>
                </template>
            </td>
            <template x-for="col in enabledCols">
                <x-tall-datatables::table.cell
                    :use-wire-navigate="$useWireNavigate"
                    x-bind:class="stickyCols.includes(col) && 'sticky left-0 border-r'"
                    x-bind:style="stickyCols.includes(col) && 'z-index: 2'"
                    class="cursor-pointer"
                    x-bind:href="record?.href ?? false">
                    <div class="flex gap-1.5">
                        <div x-html="formatter(leftAppend[col], record)">
                        </div>
                        <div>
                            <div x-html="formatter(topAppend[col], record)">
                            </div>
                            <div x-html="formatter(col, record)">
                            </div>
                            <div x-html="formatter(bottomAppend[col], record)">
                            </div>
                        </div>
                        <div x-html="formatter(rightAppend[col], record)">
                        </div>
                    </div>
                </x-tall-datatables::table.cell>
            </template>
            @if($rowActions ?? false)
                <td class="border-b border-slate-200 dark:border-slate-600 whitespace-nowrap px-3 py-4">
                    <div class="flex gap-1.5">
                        @foreach($rowActions as $rowAction)
                            {{ $rowAction }}
                        @endforeach
                    </div>
                </td>
            @endif
            {{-- Empty cell for the col selection--}}
            @if($hasSidebar)
                <td class="table-cell border-b shadow-sm border-slate-200 dark:border-slate-600 whitespace-nowrap px-3 py-4 text-sm sticky right-0">
                </td>
            @endif
        </tr>
    </template>
    <x-slot:footer>
        <template x-for="(aggregate, name) in data.aggregates">
            <tr class="hover:bg-gray-100 bg-gray-50 dark:hover:bg-secondary-800 dark:bg-secondary-900">
                <td class="border-b border-slate-200 dark:border-slate-600 whitespace-nowrap px-3 py-4 text-sm font-bold" x-text="getLabel(name)"></td>
                <template x-for="col in enabledCols">
                    <x-tall-datatables::table.cell>
                        <div
                            class="flex font-semibold"
                            x-html="formatter(col, aggregate)"
                        >
                        </div>
                    </x-tall-datatables::table.cell>
                </template>
                <td class="table-cell border-b border-slate-200 dark:border-slate-600 whitespace-nowrap px-3 py-4 text-sm">
                </td>
            </tr>
        </template>
        @if(! $hasInfiniteScroll)
            <template x-if="data.hasOwnProperty('current_page') ">
                <tr>
                    <td colspan="100%">
                        <x-tall-datatables::pagination />
                    </td>
                </tr>
            </template>
        @else
            <tr>
                <td x-intersect:enter="$wire.get('initialized') && $wire.loadMore()" colspan="100%">
                    <x-button flat spinner wire:loading.delay.longer wire:target="loadMore" class="w-full">
                        {{ __('Loading...') }}
                    </x-button>
                </td>
            </tr>
        @endif
    </x-slot:footer>
</x-tall-datatables::table>
