@if ($headline)
    <div class="w-full">
        <h1
            class="px-4 pb-2.5 text-base leading-6 font-semibold text-gray-900 dark:text-gray-50"
        >
            {{ $headline }}
        </h1>
    </div>
@endif

<div class="flex w-full justify-end gap-5">
    @if (count($this->savedFilters) > 0 && $this->showSavedFilters)
        <div>
            <x-select.styled
                x-on:select="$wire.loadSavedFilter()"
                wire:model="loadedFilterId"
                select="label:label|value:value"
                :placeholder="__('Saved filters')"
                :options="collect($this->savedFilters)
                    ->filter(fn(array $savedFilter) => data_get($savedFilter, 'settings.userFilters', false))
                    ->map(function(array $savedFilter) {
                        return [
                            'label' => $savedFilter['name'],
                            'value' => $savedFilter['id'],
                        ];
                    })
                    ->values()
                    ->toArray()
                "
                searchable
            ></x-select.styled>
        </div>
    @endif

    @if ($isSearchable)
        <div class="flex-1">
            <x-input
                type="search"
                icon="magnifying-glass"
                wire:model.live.debounce.500ms="search"
                :placeholder="__('Search in :model…', ['model' => __(\Illuminate\Support\Str::plural($modelName))])"
            ></x-input>
        </div>
    @endif

    @if ($tableActions)
        <div class="flex gap-3">
            @foreach ($tableActions as $tableAction)
                {{ $tableAction }}
            @endforeach
        </div>
    @endif
</div>
@island(name: 'badges')
    @if ($this->search
        || $this->userOrderBy
        || $this->groupBy
        || ! empty($this->sessionFilter)
        || $this->getParsedTextFilters()->isNotEmpty()
        || collect($this->userFilters)->forget('text')->isNotEmpty()
    )
        <div class="flex flex-wrap items-center gap-1.5 pt-3">
            @if ($this->search)
                <div>
                    <x-badge light flat color="purple">
                        <x-slot:text>
                            {{ __('Search') }}&nbsp;{{ $this->search }}
                        </x-slot>
                        <x-slot name="right" class="relative flex h-2 w-2 items-center">
                            <button type="button" wire:click="$set('search', '')">
                                <x-icon name="x-mark" class="h-4 w-4" />
                            </button>
                        </x-slot>
                    </x-badge>
                </div>
            @endif
            @if ($this->getParsedTextFilters()->isNotEmpty())
                <div class="flex items-center justify-center">
                    <div
                        class="dark:bg-secondary-800 pointer-events-auto flex w-full rounded-lg bg-white p-1.5 text-sm leading-5 shadow-xl shadow-black/5 hover:bg-slate-50 ring-1 ring-slate-700/10"
                    >
                        <div class="flex justify-between">
                            <div class="flex gap-1 pt-1">
                                @foreach ($this->getParsedTextFilters() as $filter)
                                    <div>
                                        <x-badge flat light color="sky">
                                            <x-slot:text>
                                                {{ $this->colLabels[$filter['column']] ?? \Illuminate\Support\Str::headline($filter['column']) }}
                                                {{ __($filter['operator']) }}
                                                @if (! in_array($filter['operator'], ['is null', 'is not null', 'has', 'has not']))
                                                    {{ $filter['operator'] !== 'like' ? $this->formatFilterBadgeValue($filter['column'], $filter['value'] ?? '') : $filter['value'] }}
                                                @endif
                                            </x-slot>
                                        </x-badge>
                                        @if (! $loop->last)
                                            <x-badge flat color="red" :text="__('and')" />
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="top-0.5 right-0.5">
                            <x-button.circle
                                color="red"
                                sm
                                icon="x-mark"
                                wire:click="$set('userFilters.text', [])"
                            />
                        </div>
                    </div>
                    @if (collect($this->userFilters)->forget('text')->isNotEmpty())
                        <div class="pl-1">
                            <x-badge flat color="red" :text="__('and')" />
                        </div>
                    @endif
                </div>
            @endif
            @if (! empty($this->sessionFilter))
                <div>
                    <div
                        class="dark:bg-secondary-800 pointer-events-auto flex w-full rounded-lg bg-white p-1.5 pr-6.5 text-sm leading-5 shadow-xl shadow-black/5 hover:bg-slate-50"
                    >
                        <x-badge light flat :text="$this->sessionFilter['name'] ?? ''" />
                        <div class="top-0.5 right-0.5">
                            <x-button.circle
                                color="red"
                                sm
                                icon="x-mark"
                                wire:click="forgetSessionFilter(true)"
                            />
                        </div>
                    </div>
                </div>
            @endif
            @foreach (collect($this->userFilters)->forget('text')->values()->all() as $orIndex => $orFilters)
                <div class="flex items-center justify-center">
                    <div
                        class="dark:bg-secondary-800 pointer-events-auto flex w-full rounded-lg bg-white p-1.5 text-sm leading-5 shadow-xl shadow-black/5 hover:bg-slate-50 ring-1 ring-slate-700/10"
                    >
                        <div class="flex justify-between">
                            <div class="flex gap-1 pt-1">
                                @foreach ($orFilters as $index => $filter)
                                    <div>
                                        <x-badge flat light color="indigo">
                                            <x-slot:text>
                                                {{ $this->colLabels[$filter['column'] ?? ''] ?? ($filter['column'] ?? '') }}
                                                {{ $filter['operator'] ?? '=' }}
                                                {{ is_array($filter['value'] ?? '') ? implode(', ', $filter['value']) : ($filter['value'] ?? '') }}
                                            </x-slot>
                                        </x-badge>
                                        @if (! $loop->last)
                                            <x-badge flat color="red" :text="__('and')" />
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="top-0.5 right-0.5">
                            <x-button.circle
                                color="red"
                                sm
                                icon="x-mark"
                                wire:click="$set('userFilters', {{ json_encode(array_values(collect($this->userFilters)->forget($orIndex)->toArray())) }})"
                            />
                        </div>
                    </div>
                    @if (! $loop->last)
                        <div class="pl-1">
                            <x-badge flat color="emerald" :text="__('or')" />
                        </div>
                    @endif
                </div>
            @endforeach
            @if ($this->userOrderBy)
                <div>
                    <x-badge light flat color="amber">
                        <x-slot:text>
                            {{ __('Order by') }}&nbsp;{{ $this->colLabels[$this->userOrderBy] ?? $this->userOrderBy }}&nbsp;{{ $this->userOrderAsc ? __('asc') : __('desc') }}
                        </x-slot>
                        <x-slot name="right" class="relative flex h-2 w-2 items-center">
                            <button type="button" wire:click="sortTable('')">
                                <x-icon name="x-mark" class="h-4 w-4" />
                            </button>
                        </x-slot>
                    </x-badge>
                </div>
            @endif
            @if ($this->groupBy)
                <div>
                    <x-badge light flat color="cyan">
                        <x-slot:text>
                            {{ __('Grouped by') }}&nbsp;{{ $this->colLabels[$this->groupBy] ?? $this->groupBy }}
                        </x-slot>
                        <x-slot name="right" class="relative flex h-2 w-2 items-center">
                            <button type="button" wire:click="setGroupBy(null)">
                                <x-icon name="x-mark" class="h-4 w-4" />
                            </button>
                        </x-slot>
                    </x-badge>
                </div>
            @endif
            <x-button rounded color="red" :text="__('Clear')" wire:click="clearFiltersAndSort" class="h-8" />
        </div>
    @endif
@endisland
