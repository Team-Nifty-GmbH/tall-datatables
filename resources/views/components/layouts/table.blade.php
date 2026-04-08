@props([
    'hasHead' => true,
    'isFilterable' => true,
    'showFilterInputs' => true,
    'tableHeadColAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'selectAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'selectedActions' => [],
    'rowActions' => [],
    'rowAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'cellAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'hasInfiniteScroll' => false,
    'hasStickyCols' => true,
    'hasSidebar' => true,
    'useWireNavigate' => true,
    'isSelectable' => false,
    'selectValue' => 'record.id',
    'allowSoftDeletes' => false,
    'showRestoreButton' => false,
    'isSortable' => false,
])
<div
    class="mt-3 flex flex-col"
    x-data="{ showSelectedActions: false, hasSelected: false }"
    x-on:change="hasSelected = $el.querySelectorAll('tbody input[type=checkbox]:checked, tbody input[type=hidden][value=true]').length > 0"
>
    <div class="relative overflow-x-auto shadow-sm sm:rounded-lg">
        <table class="dark:bg-secondary-800 min-w-full table-auto border-collapse bg-white text-sm text-gray-500 dark:text-gray-50">
            <thead style="z-index: 9">
                @if ($hasHead)
                    <tr>
                        @if ($isSelectable)
                            <x-tall-datatables::table.head-cell class="w-10 px-0! py-0!">
                                <div class="flex items-center justify-center">
                                    <x-checkbox
                                        value="*"
                                        x-on:change="
                                            if ($event.target.checked) {
                                                $wire.selected = ['*'];
                                                $wire.wildcardSelectExcluded = [];
                                            } else {
                                                $wire.selected = [];
                                                $wire.wildcardSelectExcluded = [];
                                            }
                                        "
                                        :checked="in_array('*', $this->selected)"
                                        sm
                                    />
                                </div>
                            </x-tall-datatables::table.head-cell>
                        @else
                            <th class="max-w-0"></th>
                        @endif
                        <template x-for="col in $wire.enabledCols" x-bind:key="col">
                            <x-tall-datatables::table.head-cell
                                x-bind:class="($wire.stickyCols || []).includes(col) ? 'left-0 z-10 border-r' : ''"
                                x-bind:style="($wire.stickyCols || []).includes(col) ? 'z-index: 2' : 'z-index: 1'"
                                :attributes="$tableHeadColAttributes"
                            >
                                <div class="group flex items-center gap-1">
                                    <div
                                        class="flex flex-row items-center space-x-1.5"
                                        x-bind:class="($wire.sortable || []).includes(col) || ($wire.sortable || []).includes('*') ? 'cursor-pointer' : ''"
                                        x-on:click="(($wire.sortable || []).includes(col) || ($wire.sortable || []).includes('*')) && $wire.sortTable(col, $event.shiftKey)"
                                    >
                                        <span x-text="($wire.colLabels || {})[col] || col.split('.').map(s => s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g, ' ')).join(' → ')"></span>
                                        <template x-if="$wire.userOrderBy === col">
                                            <span class="flex items-center gap-0.5">
                                                <x-icon
                                                    name="chevron-up"
                                                    class="h-3 w-3 transition-all"
                                                    x-bind:class="$wire.userOrderAsc ? '' : 'rotate-180'"
                                                />
                                                <span
                                                    x-cloak
                                                    x-show="($wire.userMultiSort || []).length > 0"
                                                    class="text-xs text-gray-400"
                                                >1</span>
                                            </span>
                                        </template>
                                        <template x-for="(sort, sortIndex) in ($wire.userMultiSort || [])" x-bind:key="sort.column">
                                            <span x-cloak x-show="sort.column === col" class="flex items-center gap-0.5">
                                                <x-icon
                                                    name="chevron-up"
                                                    class="h-3 w-3 transition-all"
                                                    x-bind:class="sort.asc ? '' : 'rotate-180'"
                                                />
                                                <span class="text-xs text-gray-400" x-text="sortIndex + 2"></span>
                                            </span>
                                        </template>
                                    </div>
                                    @if ($hasStickyCols)
                                        <div class="h-3.5 w-3.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                             x-bind:class="($wire.stickyCols || []).includes(col) && 'opacity-100!'"
                                        >
                                            <svg
                                                class="text-gray-300 hover:text-gray-500 cursor-pointer transition-colors"
                                                x-bind:class="($wire.stickyCols || []).includes(col) ? 'text-primary-500! hover:text-primary-600!' : ''"
                                                x-on:click="
                                                    let cols = [...($wire.stickyCols || [])];
                                                    if (cols.includes(col)) {
                                                        cols = cols.filter(c => c !== col);
                                                    } else {
                                                        cols.push(col);
                                                    }
                                                    $wire.stickyCols = cols;
                                                "
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
                                            class="h-4 w-4 cursor-pointer"
                                            x-on:click="$tsui.open.slide('data-table-sidebar-' + $wire.id.toLowerCase())"
                                        />
                                    @endif
                                </div>
                            </x-tall-datatables::table.head-cell>
                        </template>
                        @if ($rowActions)
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
                                        flat
                                        sm
                                        icon="cog-6-tooth"
                                        x-on:click="$tsui.open.slide('data-table-sidebar-' + $wire.id.toLowerCase())"
                                    />
                                </div>
                            </x-tall-datatables::table.head-cell>
                        @endif
                    </tr>
                    @if ($isFilterable && $showFilterInputs)
                        <template x-for="(rowIndex, i) in textFilterRows" x-bind:key="'filter-row-' + rowIndex">
                            <tr>
                                <td class="dark:bg-secondary-700/30 border-b border-gray-100 bg-gray-50/50 px-1 py-0 dark:border-secondary-700/50">
                                    <template x-if="i === 0">
                                        <div class="relative flex items-center justify-center" x-show="hasSelected" x-cloak>
                                            <x-button
                                                color="secondary"
                                                flat
                                                sm
                                                icon="chevron-down"
                                                x-on:click="showSelectedActions = !showSelectedActions"
                                            />
                                            <div
                                                x-on:click.outside="showSelectedActions = false"
                                                x-transition:enter="transition duration-200 ease-out"
                                                x-transition:enter-start="scale-95 opacity-0"
                                                x-transition:enter-end="scale-100 opacity-100"
                                                x-transition:leave="transition duration-75 ease-in"
                                                x-transition:leave-start="scale-100 opacity-100"
                                                x-transition:leave-end="scale-95 opacity-0"
                                                class="absolute left-0 top-full z-50 mt-1 min-w-48"
                                                x-cloak
                                                x-show="showSelectedActions"
                                            >
                                                <x-card x-on:click="showSelectedActions = false;">
                                                    <div class="flex flex-col gap-1.5">
                                                        @foreach ($selectedActions as $action)
                                                            {{ $action }}
                                                        @endforeach
                                                    </div>
                                                </x-card>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="i > 0">
                                        <div class="flex items-center justify-center">
                                            <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">{{ __('or') }}</span>
                                        </div>
                                    </template>
                                </td>
                                <template x-for="col in $wire.enabledCols" x-bind:key="'filter-' + rowIndex + '-' + col">
                                    <td
                                        class="group/cell dark:bg-secondary-700/30 border-b border-l border-gray-100 bg-gray-50/50 px-0 py-0 dark:border-secondary-700/50"
                                        x-bind:class="($wire.stickyCols || []).includes(col) ? 'sticky left-0 border-r' : ''"
                                        x-bind:style="($wire.stickyCols || []).includes(col) ? 'z-index: 2' : ''"
                                    >
                                        <template x-if="!($wire.filterValueLists || {})[col]">
                                            <div>
                                                <template x-for="(_, vi) in Array.from({length: getInputCount(rowIndex, col)})" x-bind:key="'vi-' + vi">
                                                    <div class="flex items-center" x-bind:class="vi > 0 && 'border-t border-gray-100 dark:border-secondary-700/50'">
                                                        <input
                                                            type="search"
                                                            class="min-w-0 flex-1 border-0 bg-transparent px-3 py-1 text-sm text-gray-600 placeholder-gray-300 outline-none focus:ring-0 dark:text-gray-300 dark:placeholder-gray-600"
                                                            x-bind:placeholder="vi === 0 ? '…' : '{{ __('and') }}…'"
                                                            x-init="$el.value = getTextFilterValue(rowIndex, col, vi)"
                                                            x-on:input.debounce.500ms="$wire.setTextFilter(col, $event.target.value, rowIndex, vi)"
                                                        />
                                                        <template x-if="getTextFilterValue(rowIndex, col, vi)">
                                                            <button
                                                                type="button"
                                                                class="shrink-0 cursor-pointer px-1 text-gray-400 transition-colors hover:text-emerald-500"
                                                                x-on:click="addColumnInput(rowIndex, col)"
                                                                title="{{ __('Add AND condition') }}"
                                                            >
                                                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                                            </button>
                                                        </template>
                                                        <template x-if="vi > 0">
                                                            <button
                                                                type="button"
                                                                class="shrink-0 cursor-pointer px-1 text-gray-400 transition-colors hover:text-red-500"
                                                                x-on:click="removeColumnInput(rowIndex, col, vi)"
                                                            >
                                                                <x-icon name="x-mark" class="h-3.5 w-3.5" />
                                                            </button>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="($wire.filterValueLists || {})[col]">
                                            <div
                                                x-effect="if (!(($wire.textFilters || {})[rowIndex] || {})[col]) { const sel = $el.querySelector('select'); if (sel) sel.value = ''; }"
                                            >
                                                <select
                                                    class="w-full border-0 bg-transparent px-3 py-1 text-sm text-gray-600 outline-none focus:ring-0 dark:text-gray-300"
                                                    x-init="$nextTick(() => $el.value = (($wire.textFilters || {})[rowIndex] || {})[col] || '')"
                                                    x-on:change="$wire.setTextFilter(col, $event.target.value, rowIndex)"
                                                >
                                                    <option value=""></option>
                                                    <template x-for="item in ($wire.filterValueLists || {})[col]" x-bind:key="item.value">
                                                        <option x-bind:value="item.value" x-text="item.label"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </template>
                                    </td>
                                </template>
                                @if ($rowActions)
                                    <td class="dark:bg-secondary-700/30 border-b border-gray-100 bg-gray-50/50 dark:border-secondary-700/50"></td>
                                @endif
                                @if ($hasSidebar)
                                    <td class="dark:bg-secondary-700/30 sticky right-0 border-b border-gray-100 bg-gray-50/50 dark:border-secondary-700/50">
                                        <div class="flex items-center justify-end gap-0.5 px-1">
                                            <template x-if="i === 0">
                                                <button
                                                    type="button"
                                                    x-on:click="addTextFilterRow()"
                                                    class="cursor-pointer rounded p-0.5 text-gray-400 transition-colors hover:text-emerald-500"
                                                    title="{{ __('Add OR filter row') }}"
                                                >
                                                    <x-icon name="plus" class="h-4 w-4" />
                                                </button>
                                            </template>
                                            <template x-if="i > 0">
                                                <button
                                                    type="button"
                                                    x-on:click="removeTextFilterRow(rowIndex)"
                                                    class="cursor-pointer rounded p-0.5 text-gray-400 transition-colors hover:text-red-500"
                                                >
                                                    <x-icon name="x-mark" class="h-4 w-4" />
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        </template>
                    @endif
                @endif
            </thead>
            {{-- isSortable is a static value (set at class level via isSortable()), not reactive --}}
            <tbody class="relative" @if($isSortable && !$this->isGrouped()) x-sort="$wire.sortRows($item, $position)" @endif>
                <tr
                    wire:loading.delay.shorter
                    wire:target.except="storeColLayout"
                    x-cloak
                    class="absolute top-0 right-0 bottom-0 left-0 z-10"
                >
                    <td>
                        <div class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-secondary-800/50">
                            <svg class="h-8 w-8 animate-spin text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </td>
                </tr>
                @island(name: 'body')
                @php extract($this->getIslandData()); @endphp
                @if (! $this->initialized)
                    <tr>
                        <td colspan="100%" class="h-24 w-24 p-8"></td>
                    </tr>
                @elseif ($this->isGrouped())
                    @if (empty($this->data['groups'] ?? []))
                        <tr>
                            <td colspan="100%" class="h-24 w-24 p-8">
                                <div class="flex w-full flex-col items-center dark:text-gray-50">
                                    <x-icon outline name="{{ $this->positiveEmptyState ? 'face-smile' : 'face-frown' }}" class="m-auto h-24 w-24" />
                                    <div class="text-center">{{ $this->positiveEmptyState ? __('All clear!') : __('No data found') }}</div>
                                </div>
                            </td>
                        </tr>
                    @else
                        <x-tall-datatables::layouts.partials.table-groups
                            :is-selectable="$isSelectable"
                            :select-value="$selectValue"
                            :select-attributes="$selectAttributes"
                            :row-attributes="$rowAttributes"
                            :cell-attributes="$cellAttributes"
                            :allow-soft-deletes="$allowSoftDeletes"
                            :use-wire-navigate="$useWireNavigate"
                            :row-actions="$rowActions"
                            :show-restore-button="$showRestoreButton"
                            :has-sidebar="$hasSidebar"
                        />
                    @endif
                @elseif (empty($this->data['data'] ?? []))
                    <tr>
                        <td colspan="100%" class="h-24 w-24 p-8">
                            <div class="flex w-full flex-col items-center dark:text-gray-50">
                                <x-icon outline name="{{ $this->positiveEmptyState ? 'face-smile' : 'face-frown' }}" class="m-auto h-24 w-24" />
                                <div class="text-center">{{ $this->positiveEmptyState ? __('All clear!') : __('No data found') }}</div>
                            </div>
                        </td>
                    </tr>
                @else
                    @foreach ($this->data['data'] ?? [] as $index => $record)
                        <x-tall-datatables::layouts.partials.table-row
                            :record="$record"
                            :index="$index"
                            :is-selectable="$isSelectable"
                            :select-value="$selectValue"
                            :select-attributes="$selectAttributes"
                            :row-attributes="$rowAttributes"
                            :cell-attributes="$cellAttributes"
                            :allow-soft-deletes="$allowSoftDeletes"
                            :use-wire-navigate="$useWireNavigate"
                            :row-actions="$rowActions"
                            :show-restore-button="$showRestoreButton"
                            :has-sidebar="$hasSidebar"
                            :is-sortable="$isSortable"
                        />
                    @endforeach
                @endif
                @endisland
            </tbody>
            <tfoot>
                @island(name: 'footer')
                @php extract($this->getIslandData()); @endphp
                @if (! empty($this->data['aggregates'] ?? []))
                    @foreach ($this->data['aggregates'] as $name => $aggregate)
                        <tr class="dark:bg-secondary-800 bg-gray-50/50 text-sm text-gray-500 dark:text-gray-400">
                            <td class="border-t border-gray-100 px-3 py-2.5 font-medium whitespace-nowrap dark:border-secondary-700/50">
                                {{ $this->getGroupLabels()[$name] ?? \Illuminate\Support\Str::headline($name) }}
                            </td>
                            @foreach ($this->enabledCols as $col)
                                <td class="border-t border-gray-100 px-3 py-2.5 whitespace-nowrap dark:border-secondary-700/50">
                                    <span class="font-medium">
                                        @if (is_array($aggregate[$col] ?? null) && isset($aggregate[$col]['display']))
                                            {!! $aggregate[$col]['display'] !!}
                                        @else
                                            {{ $aggregate[$col] ?? '' }}
                                        @endif
                                    </span>
                                </td>
                            @endforeach
                            <td class="border-t border-gray-100 px-3 py-2.5 whitespace-nowrap dark:border-secondary-700/50"></td>
                            @if ($rowActions)
                                <td class="border-t border-gray-100 px-3 py-2.5 whitespace-nowrap dark:border-secondary-700/50"></td>
                            @endif
                        </tr>
                    @endforeach
                @endif
                @if (! $hasInfiniteScroll)
                    @if (isset($this->data['current_page']))
                        <tr>
                            <td colspan="100%">
                                <x-tall-datatables::pagination />
                            </td>
                        </tr>
                    @endif
                @else
                    <tr>
                        <td x-intersect:enter="$wire.initialized && $wire.loadMore()" colspan="100%">
                            <x-button color="secondary" light flat loading="loadMore" delay="longer" class="w-full" :text="__('Loading...')" />
                        </td>
                    </tr>
                @endif
                @endisland
            </tfoot>
        </table>
    </div>
</div>
