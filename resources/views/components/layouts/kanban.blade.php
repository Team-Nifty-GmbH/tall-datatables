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
    'isSortable' => false,
])
<div class="mt-3 flex flex-col">
    @if ($isFilterable)
        <div class="flex w-full items-center justify-between gap-2 pb-3">
            @if ($this->sortable !== ['*'] || count($this->sortable) > 0)
                <div class="relative" x-data="{ open: false }">
                    <x-button
                        color="secondary"
                        flat
                        sm
                        :text="__('Sort')"
                        icon="adjustments-vertical"
                        x-on:click="open = !open"
                    />
                    <div
                        x-show="open"
                        x-cloak
                        x-on:click.outside="open = false"
                        x-transition
                        class="absolute left-0 top-full z-50 mt-1 min-w-48"
                    >
                        <x-card>
                            <div class="flex flex-col gap-0.5">
                                @foreach (($this->sortable === ['*'] ? $this->enabledCols : $this->sortable) as $sortableItem)
                                    <button
                                        type="button"
                                        class="flex cursor-pointer items-center justify-between rounded-md px-3 py-1.5 text-sm text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-secondary-700"
                                        x-on:click="open = false; $wire.sortTable('{{ $sortableItem }}')"
                                    >
                                        <span>{{ $this->colLabels[$sortableItem] ?? \Illuminate\Support\Str::headline($sortableItem) }}</span>
                                        @if ($this->userOrderBy === $sortableItem)
                                            <x-icon
                                                name="chevron-down"
                                                class="h-4 w-4 transition-all {{ $this->userOrderAsc ? '' : 'rotate-180' }}"
                                            />
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </x-card>
                    </div>
                </div>
            @endif
            @if ($hasSidebar)
                <x-button
                    color="secondary"
                    flat
                    sm
                    icon="cog-6-tooth"
                    x-on:click="$tsui.open.slide('data-table-sidebar-' + $wire.id.toLowerCase())"
                />
            @endif
        </div>
    @endif

    @island(name: 'content')
    @php
        extract($this->getIslandData());
        $formatters = $this->getFormatters();
        $modelKeyName = $this->modelKeyName;
        $enabledCols = $this->enabledCols;
        $records = $this->data['data'] ?? [];
        $grouped = collect($records)->groupBy(fn ($record) => (string) (is_array($record[$kanbanColumn] ?? null) ? ($record[$kanbanColumn]['raw'] ?? '') : ($record[$kanbanColumn] ?? '')));
    @endphp
    @if (empty($records))
        <div class="flex min-h-48 items-center justify-center py-12">
            <div class="text-center">
                <div class="text-5xl">{{ $this->positiveEmptyState ? '🎉' : '😔' }}</div>
                <div class="text-center">{{ $this->positiveEmptyState ? __('All clear!') : __('No data found') }}</div>
            </div>
        </div>
    @else
        <div
            class="flex gap-3 overflow-x-auto pb-3"
            style="min-height: 300px;"
        >
            @foreach ($kanbanLanes as $laneValue => $laneConfig)
                @php
                    $laneRecords = $grouped->get((string) $laneValue, collect());
                    $laneColor = $laneConfig['color'] ?? 'gray';
                @endphp
                <div class="flex w-72 shrink-0 flex-col rounded-lg border border-gray-200 bg-white dark:border-secondary-700 dark:bg-secondary-800">
                    <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2.5 dark:border-secondary-700">
                        <div class="flex items-center gap-2">
                            <div class="h-2 w-2 rounded-full bg-{{ $laneColor }}-500"></div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ $laneConfig['label'] ?? \Illuminate\Support\Str::headline($laneValue) }}
                            </span>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-secondary-700 dark:text-gray-400">
                            {{ $laneRecords->count() }}
                        </span>
                    </div>

                    <div
                        class="flex flex-1 flex-col gap-2 overflow-y-auto p-2"
                        x-sort="$wire.kanbanMoveItem($item, '{{ $laneValue }}')"
                        x-sort:group="kanban"
                    >
                        @foreach ($laneRecords as $index => $record)
                            <div
                                wire:key="kanban-{{ $record[$modelKeyName] ?? $index }}"
                                x-sort:item="{{ $record[$modelKeyName] ?? $index }}"
                                x-data="{ record: {{ json_encode($record) }} }"
                                x-on:click="$dispatch('data-table-row-clicked', {record})"
                                @if($allowSoftDeletes && ($record['deleted_at'] ?? null)) class="opacity-50" @endif
                                {{ $rowAttributes->merge(['class' => 'cursor-grab rounded-md border border-gray-200 bg-white p-3 transition-shadow hover:shadow-md dark:border-secondary-600 dark:bg-secondary-900']) }}
                            >
                                @if ($kanbanCardView)
                                    @include($kanbanCardView, ['record' => $record, 'enabledCols' => $enabledCols, 'colLabels' => $colLabels, 'formatters' => $formatters])
                                @else
                                    @foreach ($enabledCols as $colIndex => $col)
                                        @if ($col === $kanbanColumn)
                                            @continue
                                        @endif
                                        <div class="{{ $colIndex === 0 ? '' : 'mt-1' }}">
                                            <div class="text-sm {{ $colIndex === 0 ? 'font-semibold text-gray-900 dark:text-gray-50' : 'text-gray-500 dark:text-gray-400' }}">
                                                @if (is_array($record[$col] ?? null) && isset($record[$col]['display']))
                                                    {!! $record[$col]['display'] !!}
                                                @elseif (is_array($record[$col] ?? null) && isset($record[$col]['raw']))
                                                    {{ $record[$col]['raw'] }}
                                                @else
                                                    {{ $record[$col] ?? '' }}
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @endisland

    @island(name: 'footer')
    @php extract($this->getIslandData()); @endphp
    @if (! $hasInfiniteScroll)
        @if (isset($this->data['current_page']))
            <div class="w-full pt-3">
                <x-tall-datatables::pagination />
            </div>
        @endif
    @else
        <div
            x-intersect:enter="$wire.initialized && $wire.loadMore()"
            class="w-full pt-3"
        >
            <x-button color="secondary" flat loading="loadMore" delay="longer" class="w-full" :text="__('Loading...')" />
        </div>
    @endif
    @endisland
</div>
