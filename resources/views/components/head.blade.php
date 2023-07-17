@if($headline)
    <div class="w-full">
        <h1 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50 pb-2.5 px-4"> {{ $headline }} </h1>
    </div>
@endif
<div class="flex w-full gap-5 justify-end">
    @if($isSearchable)
        <div class="flex-1">
            <x-input
                icon="search"
                x-model.debounce.500ms="search"
                placeholder="{{ __('Search in :model…', ['model' => __(\Illuminate\Support\Str::plural($modelName))]) }}"
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
<div class="flex pt-3 items-center gap-1.5" x-cloak x-show="filters.length > 0 || orderByCol">
    <template x-for="(orFilters, orIndex) in filters">
        <div class="flex justify-center items-center">
            <div class="relative pr-6.5 pointer-events-auto w-full rounded-lg bg-white p-1.5
                                text-sm leading-5 shadow-xl shadow-black/5 hover:bg-slate-50 ring-1
                                ring-slate-700/10 dark:bg-secondary-800"
            >
                <div class="absolute top-0.5 right-0.5">
                    <x-button.circle
                        negative
                        2xs
                        icon="x"
                        x-on:click="removeFilterGroup(orIndex)"
                    />
                </div>
                <div class="flex justify-between">
                    <div class="pt-1 flex gap-1">
                        <template x-for="(filter, index) in orFilters">
                            <div>
                                <x-badge flat primary>
                                    <x-slot:label>
                                        <span x-text="filterBadge(filter)"></span>
                                    </x-slot:label>
                                    <x-slot
                                        name="append"
                                        class="relative flex items-center w-2 h-2"
                                    >
                                        <button
                                            type="button"
                                            x-on:click="removeFilter(index, orIndex)"
                                        >
                                            <x-icon
                                                name="x"
                                                class="w-4 h-4"
                                            />
                                        </button>
                                    </x-slot>
                                </x-badge>
                                <template x-if="(orFilters.length - 1) !== index">
                                    <x-badge
                                        flat negative
                                        :label="__('and')"
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
                    positive
                    :label="__('or')"
                />
            </div>
        </div>
    </template>
    <div x-cloak x-show="orderByCol">
        <x-badge flat amber>
            <x-slot:label>
                <span>{{ __('Order by') }}</span>
                <span x-text="colLabels[orderByCol] ?? orderByCol"></span>
                <span x-text="orderAsc ? '{{ __('asc') }}' : '{{ __('desc') }}'"></span>
            </x-slot:label>
            <x-slot
                name="append"
                class="relative flex items-center w-2 h-2"
            >
                <button
                    type="button"
                    x-on:click="$wire.sortTable('')"
                >
                    <x-icon
                        name="x"
                        class="w-4 h-4"
                    />
                </button>
            </x-slot>
        </x-badge>
    </div>
    <x-button
        rounded
        negative
        x-on:click="clearFilters"
        class="h-8"
    >
        {{ __('Clear') }}
    </x-button>
</div>
