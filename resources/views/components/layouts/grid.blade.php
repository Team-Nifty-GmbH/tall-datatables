@props([
    'isSelectable' => false,
    'selectValue' => 'record.id',
    'selectAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'rowAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'cellAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'isFilterable' => true,
    'showFilterInputs' => true,
    'tableHeadColAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'selectedActions' => [],
    'rowActions' => [],
    'hasInfiniteScroll' => false,
    'hasStickyCols' => true,
    'hasSidebar' => true,
    'useWireNavigate' => true,
    'hasHead' => true,
    'allowSoftDeletes' => false,
    'showRestoreButton' => false,
])
<div>
    @if ($isFilterable)
        <div
            class="flex w-full flex-row-reverse items-center justify-between pt-2"
        >
            <x-button
                color="secondary"
                light
                icon="cog"
                x-on:click="$tsui.open.slide('data-table-sidebar-' + $wire.id.toLowerCase())"
            />
            @if ($this->sortable !== ['*'] || count($this->sortable) > 0)
                <x-dropdown align="left" persistent>
                    <x-slot:action>
                        <x-button
                            color="secondary"
                            light
                            :text="__('Sort')"
                            icon="adjustments-vertical"
                        />
                    </x-slot>
                    @foreach (($this->sortable === ['*'] ? $this->enabledCols : $this->sortable) as $sortableItem)
                        <a
                            class="text-secondary-600 hover:text-secondary-900 hover:bg-secondary-100 dark:text-secondary-400 dark:hover:bg-secondary-700 flex cursor-pointer items-center justify-between rounded-md px-4 py-2 text-sm transition-colors duration-150"
                            wire:click="sortTable('{{ $sortableItem }}')"
                        >
                            <div>{{ $this->colLabels[$sortableItem] ?? \Illuminate\Support\Str::headline($sortableItem) }}</div>
                            @if ($this->userOrderBy === $sortableItem)
                                <x-icon
                                    name="chevron-down"
                                    class="h-4 w-4 transition-all {{ $this->userOrderAsc ? '' : 'rotate-180' }}"
                                />
                            @endif
                        </a>
                    @endforeach
                </x-dropdown>
            @endif
        </div>
    @endif

    @if ($this->initialized && empty($this->data['data'] ?? []))
        <div class="h-24 w-24 w-full p-8">
            <div class="w-full flex-col items-center dark:text-gray-50">
                <x-icon name="face-frown" class="m-auto h-24 w-24" />
                <div class="text-center">
                    {{ __('No data found') }}
                </div>
            </div>
        </div>
    @endif
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
        @foreach ($this->data['data'] ?? [] as $index => $record)
            <div
                wire:key="grid-{{ $record[$this->modelKeyName] ?? $index }}"
                x-on:click="$dispatch('data-table-row-clicked', {{ json_encode($record) }})"
                {{ $rowAttributes->merge(['class' => 'hover:bg-gray-100 dark:hover:bg-secondary-900 rounded-md pb-1.5']) }}
            >
                <a
                    class="relative text-sm font-medium text-gray-500 dark:text-gray-50"
                    @if ($record['href'] ?? null)
                        href="{{ $record['href'] }}"
                        @if ($useWireNavigate)
                            wire:navigate
                        @endif
                    @endif
                >
                    @foreach ($this->enabledCols as $colIndex => $col)
                        @php
                            $formatters = $this->getFormatters();
                            $isImage = ($formatters[$col] ?? null) === 'image';
                        @endphp
                        @if ($isImage)
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
                                    src="{{ is_array($record[$col] ?? null) ? ($record[$col]['raw'] ?? '') : ($record[$col] ?? '') }}"
                                    class="h-full w-full object-cover object-center"
                                />
                            </div>
                        @else
                            <div
                                class="mt-4 flex px-2 {{ $colIndex === 1 ? 'font-semibold' : '' }}"
                            >
                                <div class="flex flex-wrap gap-1">
                                    @if (is_array($record[$col] ?? null) && isset($record[$col]['display']))
                                        {!! $record[$col]['display'] !!}
                                    @elseif (is_array($record[$col] ?? null) && isset($record[$col]['raw']))
                                        {{ $record[$col]['raw'] }}
                                    @else
                                        {{ $record[$col] ?? '' }}
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </a>
            </div>
        @endforeach
    </div>
    @if (! $hasInfiniteScroll)
        @if (isset($this->data['current_page']))
            <div class="w-full">
                <x-tall-datatables::pagination />
            </div>
        @endif
    @else
        <div
            x-intersect:enter="$wire.initialized && $wire.loadMore()"
            class="w-full"
        >
            <x-button
                color="secondary"
                light
                flat
                delay="longer"
                loading="loadMore"
                class="w-full"
                :text="__('Loading...')"
            />
        </div>
    @endif
</div>
