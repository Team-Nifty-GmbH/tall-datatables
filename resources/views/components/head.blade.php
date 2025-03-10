@if($headline)
    <div class="w-full">
        <h1 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50 pb-2.5 px-4"> {{ $headline }} </h1>
    </div>
@endif
<div class="flex w-full gap-5 justify-end">
    @if(count($this->savedFilters) > 0 && $this->showSavedFilters)
        <div>
            <x-select.styled
                x-on:select="loadSavedFilter()"
                wire:model="loadedFilterId"
                :placeholder="__('Saved filters')"
                :options="collect($this->savedFilters)
                    ->filter(fn(array $savedFilter) => data_get($savedFilter, 'settings.userFilters', false))
                    ->map(function(array $savedFilter) {
                        return [
                            'label' => $savedFilter['name'],
                            'value' => $savedFilter['id'],
                        ];
                    })
                    ->toArray()"
                required
            >
            </x-select.styled>
        </div>
    @endif
    @if($isSearchable)
        <div class="flex-1">
            <x-input
                type="search"
                icon="magnifying-glass"
                x-model.debounce.500ms="search"
                :placeholder="__('Search in :model…', ['model' => __(\Illuminate\Support\Str::plural($modelName))])"
            >
            </x-input>
        </div>
    @endif
    @if($tableActions)
        <div class="flex gap-3">
            @foreach($tableActions as $tableAction)
                {{$tableAction}}
            @endforeach
        </div>
    @endif
</div>
<div class="flex flex-wrap pt-3 items-center gap-1.5" x-cloak x-show="filters.length > 0 || orderByCol || Object.keys($wire.sessionFilter).length !== 0">
    <div x-show="Object.keys($wire.sessionFilter).length !== 0" x-cloak>
        <div class="relative pr-6.5 pointer-events-auto w-full rounded-lg bg-white p-1.5 text-sm leading-5 shadow-xl shadow-black/5 hover:bg-slate-50 dark:bg-secondary-800">
            <div class="absolute top-0.5 right-0.5">
                <x-button.circle
                    color="red"
                    sm
                    icon="x-mark"
                    x-on:click="$wire.forgetSessionFilter(true)"
                />
            </div>
            <x-badge flat>
                <x-slot:text>
                    <span x-text="$wire.sessionFilter.name"></span>
                </x-slot:text>
            </x-badge>
        </div>
    </div>
    <template x-for="(orFilters, orIndex) in filters">
        <div class="flex justify-center items-center">
            <div class="relative pr-6.5 pointer-events-auto w-full rounded-lg bg-white p-1.5
                                text-sm leading-5 shadow-xl shadow-black/5 hover:bg-slate-50 dark:bg-secondary-800"
                 x-on:click="filterIndex = orIndex"
                 x-bind:class="filterIndex === orIndex ? 'ring-2 ring-indigo-600' : 'ring-1 ring-slate-700/10'"
            >
                <div class="absolute top-0.5 right-0.5">
                    <x-button.circle
                        color="red"
                        sm
                        icon="x-mark"
                        x-on:click="removeFilterGroup(orIndex)"
                    />
                </div>
                <div class="flex justify-between">
                    <div class="pt-1 flex gap-1">
                        <template x-for="(filter, index) in orFilters">
                            <div>
                                <x-badge flat color="indigo">
                                    <x-slot:text>
                                        <span x-text="filterBadge(filter)"></span>
                                    </x-slot:text>
                                    <x-slot
                                        name="right"
                                        class="relative flex items-center w-2 h-2"
                                    >
                                        <button
                                            type="button"
                                            x-on:click="removeFilter(index, orIndex)"
                                        >
                                            <x-icon
                                                name="x-mark"
                                                class="w-4 h-4"
                                            />
                                        </button>
                                    </x-slot>
                                </x-badge>
                                <template x-if="(orFilters.length - 1) !== index">
                                    <x-badge
                                        flat color="red"
                                        :text="__('and')"
                                    />
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div
                class="pl-1"
                x-claok
                x-show="(filters.length - 1) !== orIndex"
            >
                <x-badge
                    flat
                    color="emerald"
                    :text="__('or')"
                />
            </div>
        </div>
    </template>
    <div x-cloak x-show="orderByCol">
        <x-badge flat color="amber">
            <x-slot:text>
                <span>{{ __('Order by') }}</span>
                <span x-text="getLabel(orderByCol)"></span>
                <span x-text="orderAsc ? '{{ __('asc') }}' : '{{ __('desc') }}'"></span>
            </x-slot:text>
            <x-slot
                name="right"
                class="relative flex items-center w-2 h-2"
            >
                <button
                    type="button"
                    x-on:click="$wire.sortTable('')"
                >
                    <x-icon
                        name="x-mark"
                        class="w-4 h-4"
                    />
                </button>
            </x-slot>
        </x-badge>
    </div>
    <x-button
        rounded
        color="red"
        x-on:click="clearFilters"
        class="h-8"
    >
        {{ __('Clear') }}
    </x-button>
</div>
