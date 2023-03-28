<x-tall-datatables::table class="relative">
    <tr wire:loading.delay class="absolute bottom-0 top-0 right-0 w-full">
        <td>
            <x-tall-datatables::spinner />
        </td>
    </tr>
    @if($hasHead)
        <x-slot:header>
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
            <template x-for="(col, index) in cols">
                <x-tall-datatables::table.head-cell :attributes="$tableHeadColAttributes">
                    <div class="flex">
                        <div
                            type="button"
                            wire:loading.attr="disabled"
                            class="flex flex-row items-center space-x-1.5"
                            x-on:click="sortable.includes(col) && $wire.sortTable(col)"
                            x-bind:class="sortable.includes(col) ? 'cursor-pointer' : ''"
                        >
                            <span x-text="colLabels[col]"></span>
                            <x-icon
                                x-bind:class="Object.keys(sortable).length && orderByCol === col
                                ? (orderAsc || 'rotate-180')
                                : 'opacity-0'"
                                name="chevron-up"
                                class="h-4 w-4 transition-all"
                            />
                        </div>
                        @if($isFilterable)
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
            <x-tall-datatables::table.head-cell class="w-4 flex flex-row-reverse">
                <div class="flex w-full flex-row-reverse items-center">
                    <x-button
                        icon="cog"
                        x-on:click="loadSidebar()"
                    />
                </div>
            </x-tall-datatables::table.head-cell>
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
                            x-on:change="$dispatch('data-table-record-selected', {record: record, index: index, value: $el.checked})"
                            x-bind:value="record.id"
                            x-model="selected"
                        />
                    </div>
                </template>
            </td>
            <template x-for="col in cols">
                <x-tall-datatables::table.cell class="cursor-pointer" x-bind:href="record?.href ?? false">
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
            <td class="table-cell border-b border-slate-200 dark:border-slate-600 whitespace-nowrap px-3 py-4 text-sm">
            </td>
        </tr>
    </template>
    <x-slot:footer>
        <template x-for="(aggregate, name) in data.aggregates">
            <tr class="hover:bg-gray-100 bg-gray-50 dark:hover:bg-secondary-800 dark:bg-secondary-900">
                <td class="border-b border-slate-200 dark:border-slate-600 whitespace-nowrap px-3 py-4 text-sm font-bold" x-text="name"></td>
                <template x-for="col in cols">
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
                    <x-button flat spinner wire:loading wire:target="loadMore" class="w-full">
                        {{ __('Loading...') }}
                    </x-button>
                </td>
            </tr>
        @endif
    </x-slot:footer>
</x-tall-datatables::table>
