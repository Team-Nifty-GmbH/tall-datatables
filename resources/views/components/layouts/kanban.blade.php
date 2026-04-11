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

    @if (! $hasHead && ($tableActions ?? false))
        <div class="flex justify-end gap-1.5 pb-3">
            @foreach ($tableActions as $tableAction)
                {{ $tableAction }}
            @endforeach
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
                    $laneColorClass = str_starts_with($laneColor, 'bg-') ? $laneColor : match ($laneColor) {
                        'red' => 'bg-red-500',
                        'orange' => 'bg-orange-500',
                        'amber' => 'bg-amber-500',
                        'yellow' => 'bg-yellow-500',
                        'lime' => 'bg-lime-500',
                        'green' => 'bg-green-500',
                        'emerald' => 'bg-emerald-500',
                        'teal' => 'bg-teal-500',
                        'cyan' => 'bg-cyan-500',
                        'sky' => 'bg-sky-500',
                        'blue' => 'bg-blue-500',
                        'indigo' => 'bg-indigo-500',
                        'violet' => 'bg-violet-500',
                        'purple' => 'bg-purple-500',
                        'fuchsia' => 'bg-fuchsia-500',
                        'pink' => 'bg-pink-500',
                        'rose' => 'bg-rose-500',
                        'neutral' => 'bg-neutral-500',
                        default => 'bg-gray-500',
                    };
                @endphp
                <div class="flex min-w-48 flex-1 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-secondary-700 dark:bg-secondary-800">
                    <div class="h-1 {{ $laneColorClass }}"></div>
                    <div class="flex items-center justify-between px-3 py-2.5">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ $laneConfig['label'] ?? \Illuminate\Support\Str::headline($laneValue) }}
                        </span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-secondary-700 dark:text-gray-400">
                            {{ $laneRecords->count() }}
                        </span>
                    </div>

                    <div
                        class="flex flex-1 flex-col gap-2 overflow-y-auto p-2"
                        x-sort="$wire.kanbanMoveItem($item, '{{ $laneValue }}')@if ($isSortable); $wire.sortRows($item, $position)@endif"
                        x-sort:group="kanban"
                    >
                        @foreach ($laneRecords as $index => $record)
                            <div
                                wire:key="kanban-{{ $record[$modelKeyName] ?? $index }}"
                                x-sort:item="{{ $record[$modelKeyName] ?? $index }}"
                                x-data="{ record: {{ json_encode($record) }}, actionsOpen: false }"
                                x-on:click="$dispatch('data-table-row-clicked', {record})"
                                @if($allowSoftDeletes && ($record['deleted_at'] ?? null)) class="opacity-50" @endif
                                {{ $rowAttributes->merge(['class' => 'group relative cursor-move rounded-md border border-gray-200 bg-white p-3 transition-shadow hover:shadow-md dark:border-secondary-600 dark:bg-secondary-900']) }}
                            >
                                @if ($rowActions)
                                    <div
                                        x-on:click.stop
                                        class="absolute top-1 z-10 opacity-0 transition-opacity group-hover:opacity-100"
                                        style="right: 4px;"
                                        x-bind:class="actionsOpen && 'opacity-100!'"
                                    >
                                        <button
                                            type="button"
                                            class="cursor-pointer rounded-full bg-white/80 p-0.5 text-gray-500 shadow-sm backdrop-blur-sm transition-colors hover:bg-white hover:text-gray-900 dark:bg-secondary-800/80 dark:text-gray-400 dark:hover:bg-secondary-800 dark:hover:text-gray-200"
                                            x-on:click="actionsOpen = !actionsOpen"
                                        >
                                            <x-icon name="ellipsis-vertical" class="h-4 w-4" />
                                        </button>
                                        <div
                                            x-show="actionsOpen"
                                            x-cloak
                                            x-on:click.outside="actionsOpen = false"
                                            x-transition
                                            class="absolute top-full right-0 z-50 mt-1 min-w-36"
                                        >
                                            <x-card>
                                                <div class="flex flex-col gap-1.5" x-on:click="actionsOpen = false">
                                                    @foreach ($rowActions as $rowAction)
                                                        {{ $rowAction }}
                                                    @endforeach
                                                </div>
                                            </x-card>
                                        </div>
                                    </div>
                                @endif
                                @if ($kanbanCardView)
                                    @include($kanbanCardView, ['record' => $record, 'enabledCols' => $enabledCols, 'colLabels' => $colLabels, 'formatters' => $formatters])
                                @else
                                    <div class="flex flex-col gap-3">
                                        @foreach ($enabledCols as $colIndex => $col)
                                            @if ($col === $kanbanColumn)
                                                @continue
                                            @endif
                                            <div>
                                                @if ($colIndex > 0)
                                                    <div class="mb-0.5 text-xs leading-none text-gray-400 dark:text-gray-500">
                                                        {{ $colLabels[$col] ?? \Illuminate\Support\Str::headline($col) }}
                                                    </div>
                                                @endif
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
                                    </div>
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
    @endisland
</div>
