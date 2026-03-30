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
])
<div class="mt-3 flex flex-col">
    <div class="relative overflow-x-auto shadow ring-1 ring-black/5 sm:rounded-lg">
        <table class="dark:divide-secondary-700 dark:bg-secondary-800 min-w-full table-auto border-collapse divide-y divide-gray-300 rounded-md bg-white text-gray-500 dark:text-gray-50">
            <thead class="font-semibold" style="z-index: 9">
                @if ($hasHead)
                    <tr>
                        @if ($isSelectable)
                            <x-tall-datatables::table.head-cell class="min-w-24 px-0! py-0!">
                                <div class="flex items-center justify-center gap-1.5">
                                    <input
                                        type="checkbox"
                                        value="*"
                                        x-on:change="
                                            if ($event.target.checked) {
                                                $wire.selected = {{ json_encode(
                                                    $selectValue === 'index'
                                                        ? range(0, max(0, count($this->data['data'] ?? []) - 1))
                                                        : array_column($this->data['data'] ?? [], $this->modelKeyName)
                                                ) }};
                                                $wire.selected.push('*');
                                            } else {
                                                $wire.selected = [];
                                                $wire.wildcardSelectExcluded = [];
                                            }
                                        "
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800"
                                    />
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
                            </x-tall-datatables::table.head-cell>
                        @else
                            <th class="max-w-0"></th>
                        @endif
                        @foreach ($this->enabledCols as $col)
                            <x-tall-datatables::table.head-cell
                                :class="in_array($col, $this->stickyCols) ? 'left-0 z-10 border-r' : ''"
                                :style="in_array($col, $this->stickyCols) ? 'z-index: 2' : ''"
                                :attributes="$tableHeadColAttributes"
                            >
                                <div class="flex">
                                    <div
                                        type="button"
                                        wire:loading.attr="disabled"
                                        class="group flex flex-row items-center space-x-1.5 {{ in_array($col, $this->sortable) || $this->sortable === ['*'] ? 'cursor-pointer' : '' }}"
                                        wire:click="{{ in_array($col, $this->sortable) || $this->sortable === ['*'] ? "sortTable('{$col}')" : '' }}"
                                    >
                                        <span>{{ $this->colLabels[$col] ?? \Illuminate\Support\Str::headline($col) }}</span>
                                        @if ($this->userOrderBy === $col)
                                            <x-icon
                                                name="chevron-up"
                                                class="h-4 w-4 transition-all {{ $this->userOrderAsc ? '' : 'rotate-180' }}"
                                            />
                                        @endif
                                    </div>
                                    @if ($hasStickyCols)
                                        <div class="h-4 w-4">
                                            <svg
                                                class="{{ in_array($col, $this->stickyCols) ? 'fill-indigo-600' : '' }}"
                                                x-on:click="
                                                    let cols = [...$wire.stickyCols];
                                                    if (cols.includes('{{ $col }}')) {
                                                        cols = cols.filter(c => c !== '{{ $col }}');
                                                    } else {
                                                        cols.push('{{ $col }}');
                                                    }
                                                    $wire.stickyCols = cols;
                                                    stickyCols = cols;
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
                        @endforeach
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
                                        light
                                        icon="cog"
                                        x-on:click="$tsui.open.slide('data-table-sidebar-' + $wire.id.toLowerCase())"
                                    />
                                </div>
                            </x-tall-datatables::table.head-cell>
                        @endif
                    </tr>
                    @if ($isFilterable && $showFilterInputs)
                        <tr>
                            <td class="dark:bg-secondary-600 max-w-0 bg-gray-50"></td>
                            @foreach ($this->enabledCols as $col)
                                <td
                                    class="dark:bg-secondary-600 bg-gray-50 px-2 py-1 {{ in_array($col, $this->stickyCols) ? 'sticky left-0 border-r' : '' }}"
                                    style="{{ in_array($col, $this->stickyCols) ? 'z-index: 2' : '' }}"
                                >
                                    @if (! isset($this->filterValueLists[$col]))
                                        <div>
                                            <x-input
                                                type="search"
                                                class="p-1"
                                                wire:model.live.debounce.500ms="userFilters.text.{{ $col }}"
                                            />
                                        </div>
                                    @else
                                        <x-select.native
                                            wire:model.live="userFilters.text.{{ $col }}"
                                            placeholder="{{ __('Value') }}"
                                        >
                                            <option value=""></option>
                                            @foreach ($this->filterValueLists[$col] as $item)
                                                <option value="{{ $item['value'] }}">{{ $item['label'] }}</option>
                                            @endforeach
                                        </x-select.native>
                                    @endif
                                </td>
                            @endforeach
                            @if ($rowActions)
                                <td class="dark:bg-secondary-800 bg-gray-50"></td>
                            @endif
                            @if ($hasSidebar)
                                <td class="dark:bg-secondary-800 bg-gray-50"></td>
                            @endif
                        </tr>
                    @endif
                @endif
            </thead>
            <tbody class="relative">
                <tr
                    wire:loading.delay.long
                    wire:target.except="storeColLayout"
                    x-cloak
                    class="absolute top-0 right-0 bottom-0 left-0 z-10"
                >
                    <td>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <x-loading loading="loadData,sortTable,gotoPage,setPerPage,startSearch,applyUserFilters,loadMore" delay="long" />
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
                                <div class="w-full flex-col items-center dark:text-gray-50">
                                    <x-icon outline name="face-frown" class="m-auto h-24 w-24" />
                                    <div class="text-center">{{ __('No data found') }}</div>
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
                            <div class="w-full flex-col items-center dark:text-gray-50">
                                <x-icon outline name="face-frown" class="m-auto h-24 w-24" />
                                <div class="text-center">{{ __('No data found') }}</div>
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
                        <tr class="dark:hover:bg-secondary-800 dark:bg-secondary-900 bg-gray-50 hover:bg-gray-100">
                            <td class="border-b border-slate-200 px-3 py-4 text-sm font-bold whitespace-nowrap dark:border-slate-600">
                                {{ $this->getGroupLabels()[$name] ?? \Illuminate\Support\Str::headline($name) }}
                            </td>
                            @foreach ($this->enabledCols as $col)
                                <x-tall-datatables::table.cell>
                                    <div class="flex font-semibold">
                                        @if (is_array($aggregate[$col] ?? null) && isset($aggregate[$col]['display']))
                                            {!! $aggregate[$col]['display'] !!}
                                        @else
                                            {{ $aggregate[$col] ?? '' }}
                                        @endif
                                    </div>
                                </x-tall-datatables::table.cell>
                            @endforeach
                            <td class="table-cell border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap dark:border-slate-600"></td>
                            @if ($rowActions)
                                <td class="table-cell border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap dark:border-slate-600"></td>
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
    @if ($isSelectable)
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
        >
            <x-card x-on:click="showSelectedActions = false;">
                <div class="flex flex-col gap-1.5">
                    @foreach ($selectedActions as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </x-card>
        </div>
    @endif
</div>
