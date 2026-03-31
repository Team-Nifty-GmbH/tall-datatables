@if ($headline)
    <div class="w-full">
        <h1
            class="px-4 pb-2.5 text-base leading-6 font-semibold text-gray-900 dark:text-gray-50"
        >
            {{ $headline }}
        </h1>
    </div>
@endif

<div class="flex w-full justify-end gap-2">
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
        <div class="flex gap-1.5">
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
        || ! empty($this->userFilters)
    )
        <div class="flex flex-wrap items-center gap-2 pt-3">
            @if ($this->search)
                <div>
                    <x-badge light flat>
                        <x-slot:text>
                            {{ __('Search') }}&nbsp;{{ $this->search }}
                        </x-slot>
                        <x-slot name="right" class="relative flex h-2 w-2 items-center">
                            <button type="button" class="cursor-pointer" wire:click="$set('search', '')">
                                <x-icon name="x-mark" class="h-4 w-4" />
                            </button>
                        </x-slot>
                    </x-badge>
                </div>
            @endif
            @if (! empty($this->sessionFilter))
                <div>
                    <x-badge light flat>
                        <x-slot:text>{{ $this->sessionFilter['name'] ?? '' }}</x-slot>
                        <x-slot name="right" class="relative flex h-2 w-2 items-center">
                            <button type="button" class="cursor-pointer" wire:click="forgetSessionFilter(true)">
                                <x-icon name="x-mark" class="h-4 w-4" />
                            </button>
                        </x-slot>
                    </x-badge>
                </div>
            @endif
            @foreach ($this->userFilters as $orIndex => $orFilters)
                @if (! is_array($orFilters))
                    @continue
                @endif
                @foreach ($orFilters as $filterIndex => $filter)
                    @if (! is_array($filter) || empty($filter['column'] ?? ''))
                        @continue
                    @endif
                    @php
                        $displayValue = $filter['value'] ?? '';
                        $operator = $filter['operator'] ?? '=';
                        // Translate enum/state values
                        if ($operator === '=' && isset($this->filterValueLists[$filter['column']])) {
                            $label = collect($this->filterValueLists[$filter['column']])->firstWhere('value', $displayValue);
                            $displayValue = $label['label'] ?? $displayValue;
                        }
                        // Strip LIKE wildcards
                        if ($operator === 'like' && is_string($displayValue)) {
                            $displayValue = trim($displayValue, '%');
                        }
                        if (is_array($displayValue)) {
                            $displayValue = implode(', ', array_map(fn ($v) => is_array($v) ? json_encode($v) : $v, $displayValue));
                        }
                    @endphp
                    <div>
                        <x-badge flat light>
                            <x-slot:text>
                                {{ $this->colLabels[$filter['column']] ?? \Illuminate\Support\Str::headline($filter['column']) }}
                                {{ __($operator) }}
                                @if (! in_array($operator, ['is null', 'is not null', 'has', 'has not']))
                                    {{ $displayValue }}
                                @endif
                            </x-slot>
                            <x-slot name="right" class="relative flex h-2 w-2 items-center">
                                <button type="button" class="cursor-pointer" wire:click="removeFilter({{ $orIndex }}, {{ $filterIndex }})">
                                    <x-icon name="x-mark" class="h-4 w-4" />
                                </button>
                            </x-slot>
                        </x-badge>
                    </div>
                    @if (! $loop->last)
                        <x-badge flat color="red" :text="__('and')" />
                    @endif
                @endforeach
                @if (! $loop->last)
                    <x-badge flat color="emerald" :text="__('or')" />
                @endif
            @endforeach
            @if ($this->userOrderBy)
                <div>
                    <x-badge light flat color="amber">
                        <x-slot:text>
                            {{ __('Order by') }}&nbsp;{{ $this->colLabels[$this->userOrderBy] ?? $this->userOrderBy }}&nbsp;{{ $this->userOrderAsc ? __('asc') : __('desc') }}
                        </x-slot>
                        <x-slot name="right" class="relative flex h-2 w-2 items-center">
                            <button type="button" class="cursor-pointer" wire:click="sortTable('')">
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
                            <button type="button" class="cursor-pointer" wire:click="setGroupBy(null)">
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
