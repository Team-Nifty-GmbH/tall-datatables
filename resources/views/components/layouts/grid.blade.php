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
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="scale-95 opacity-0"
                        x-transition:enter-end="scale-100 opacity-100"
                        x-transition:leave="transition duration-75 ease-in"
                        x-transition:leave-start="scale-100 opacity-100"
                        x-transition:leave-end="scale-95 opacity-0"
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

    <div class="relative">
        <div
            wire:loading.delay.shorter
            wire:target.except="storeColLayout"
            x-cloak
            class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-secondary-800/50"
        >
            <svg class="h-8 w-8 animate-spin text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        @island(name: 'body')
        @php extract($this->getIslandData()); @endphp
        @if (! $this->initialized)
            <div class="h-24 w-full p-8"></div>
        @elseif (empty($this->data['data'] ?? []))
            <div class="h-24 w-full p-8">
                <div class="flex w-full flex-col items-center dark:text-gray-50">
                    <x-icon outline name="face-frown" class="m-auto h-24 w-24" />
                    <div class="text-center">{{ __('No data found') }}</div>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                @php
                    $formatters = $this->getFormatters();
                    $modelKeyName = $this->modelKeyName;
                    $enabledCols = $this->enabledCols;
                @endphp
                @foreach ($this->data['data'] ?? [] as $index => $record)
                    <div
                        wire:key="grid-{{ $record[$modelKeyName] ?? $index }}"
                        x-data="{ record: {{ json_encode($record) }}, hover: false, actionsOpen: false }"
                        x-on:click="$dispatch('data-table-row-clicked', {record})"
                        x-on:mouseenter="hover = true"
                        x-on:mouseleave="if (!actionsOpen) hover = false"
                        @if($allowSoftDeletes && ($record['deleted_at'] ?? null)) class="opacity-50" @endif
                        {{ $rowAttributes->merge(['class' => 'relative rounded-md pb-1.5 transition-colors hover:z-30 hover:bg-gray-100 dark:hover:bg-secondary-900']) }}
                    >
                        @if ($isSelectable)
                            <div
                                x-on:click.stop
                                x-show="hover"
                                x-cloak
                                x-transition.opacity.duration.150ms
                                class="absolute top-2 left-2 z-10"
                                {{ $selectAttributes }}
                            >
                                <x-checkbox
                                    value="{{ $record[$modelKeyName] ?? $index }}"
                                    wire:model.number="selected"
                                    sm
                                />
                            </div>
                        @endif

                        @if ($rowActions || ($showRestoreButton && $allowSoftDeletes))
                            <div
                                x-on:click.stop
                                x-show="hover || actionsOpen"
                                x-cloak
                                x-transition.opacity.duration.150ms
                                class="absolute top-2 right-2 z-20"
                            >
                                <button
                                    type="button"
                                    class="cursor-pointer rounded-full bg-white/80 p-1 text-gray-600 shadow-sm backdrop-blur-sm transition-colors hover:bg-white hover:text-gray-900 dark:bg-secondary-800/80 dark:text-gray-400 dark:hover:bg-secondary-800 dark:hover:text-gray-200"
                                    x-on:click="actionsOpen = !actionsOpen"
                                >
                                    <x-icon name="ellipsis-vertical" class="h-5 w-5" />
                                </button>
                                <div
                                    x-show="actionsOpen"
                                    x-cloak
                                    x-on:click.outside="actionsOpen = false"
                                    x-transition
                                    class="absolute right-0 top-full z-50 mt-1 min-w-36"
                                >
                                    <x-card>
                                        <div class="flex flex-col gap-1.5">
                                            @if (! ($allowSoftDeletes && ($record['deleted_at'] ?? null)))
                                                @foreach ($rowActions as $rowAction)
                                                    {{ $rowAction }}
                                                @endforeach
                                            @endif
                                            @if ($showRestoreButton && $allowSoftDeletes && ($record['deleted_at'] ?? null))
                                                <x-button
                                                    color="indigo"
                                                    sm
                                                    :text="__('Restore')"
                                                    wire:click="restore({{ $record[$modelKeyName] ?? 0 }})"
                                                />
                                            @endif
                                        </div>
                                    </x-card>
                                </div>
                            </div>
                        @endif

                        <a
                            class="block"
                            @if ($record['href'] ?? null)
                                href="{{ $record['href'] }}"
                                @if ($useWireNavigate)
                                    wire:navigate
                                @endif
                            @endif
                        >
                            @foreach ($enabledCols as $colIndex => $col)
                                @php
                                    $isImage = ($formatters[$col] ?? null) === 'image';
                                @endphp
                                @if ($isImage)
                                    @php
                                        $imgSrc = is_array($record[$col] ?? null) ? ($record[$col]['raw'] ?? '') : ($record[$col] ?? '');
                                        $hasRealImage = $imgSrc && ! str_contains($imgSrc, '/icons/');
                                    @endphp
                                    @if ($hasRealImage)
                                        <div class="relative h-48 w-full overflow-hidden rounded-t-md">
                                            <img
                                                src="{{ $imgSrc }}"
                                                class="h-full w-full object-cover object-center"
                                                loading="lazy"
                                            />
                                        </div>
                                    @else
                                        <div class="flex h-32 w-full items-center justify-center rounded-t-md bg-gray-50 dark:bg-secondary-700/30">
                                            <x-icon name="photo" class="h-12 w-12 text-gray-300 dark:text-secondary-600" />
                                        </div>
                                    @endif
                                @else
                                    <div
                                        class="px-3 {{ $colIndex === 0 ? 'pt-3' : 'pt-1' }} {{ $colIndex === count($enabledCols) - 1 ? 'pb-3' : '' }}"
                                    >
                                        <div class="flex flex-wrap gap-1 text-sm {{ $colIndex === 0 ? 'font-semibold text-gray-900 dark:text-gray-50' : 'text-gray-500 dark:text-gray-400' }}">
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
        @endif
        @endisland
    </div>

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
