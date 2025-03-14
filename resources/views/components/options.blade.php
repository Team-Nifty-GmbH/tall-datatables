<div
    class="mt-2"
    x-data="{
        searchRelations: null,
        searchColumns: null,
        searchAggregatable: null,
        dateCalculation: 0,
    }"
>
    @if (auth()->user() && method_exists(auth()->user(), 'datatableUserSettings'))
        <x-modal
            persistent
            id="save-filter"
            :title="__('Save filter')"
            x-on:close="filterName = ''; permanent = false;"
        >
            <x-input
                required
                :label="__('Filter name')"
                x-model="filterName"
            />
            <div class="flex flex-col gap-1.5 pt-3">
                <x-checkbox :label="__('Permanent')" x-model="permanent" />
                <x-checkbox
                    :label="__('With column layout')"
                    x-model="withEnabledCols"
                />
            </div>
            <x-slot:footer>
                <x-button
                    color="secondary"
                    light
                    flat
                    :text="__('Cancel')"
                    x-on:click="$modalClose('save-filter')"
                />
                <x-button
                    :text="__('Save')"
                    x-on:click="$wire.$parent.saveFilter(filterName, permanent, withEnabledCols).then(() => $modalClose('save-filter'));"
                />
            </x-slot>
        </x-modal>
    @endif

    @if ($this->isFilterable)
        <x-modal persistent id="date-calculation">
            <div class="flex flex-col gap-3">
                <div class="flex gap-3">
                    <x-button
                        x-bind:class="newFilterCalculation.operator === '-' && 'ring-2 ring-offset-2'"
                        x-on:click="newFilterCalculation.operator = '-'"
                        color="red"
                    >
                        -
                    </x-button>
                    <x-button
                        x-bind:class="newFilterCalculation.operator === '+' && 'ring-2 ring-offset-2'"
                        x-on:click="newFilterCalculation.operator = '+'"
                        color="emerald"
                    >
                        +
                    </x-button>
                    <x-number min="0" x-model="newFilterCalculation.value" />
                    <x-select.styled
                        x-model="newFilterCalculation.unit"
                        :options="[
                            [
                                'label' => __('Minutes'),
                                'value' => 'minutes',
                            ],
                            [
                                'label' => __('Hours'),
                                'value' => 'hours',
                            ],
                            [
                                'label' => __('Days'),
                                'value' => 'days',
                            ],
                            [
                                'label' => __('Weeks'),
                                'value' => 'weeks',
                            ],
                            [
                                'label' => __('Months'),
                                'value' => 'months',
                            ],
                            [
                                'label' => __('Years'),
                                'value' => 'years',
                            ]
                        ]"
                    />
                </div>
                <div class="flex w-full gap-3">
                    <div>
                        <x-radio
                            :label="__('Same time')"
                            value=""
                            x-model="newFilterCalculation.is_start_of"
                        />
                        <x-radio
                            :label="__('Start of')"
                            value="1"
                            x-model="newFilterCalculation.is_start_of"
                        />
                        <x-radio
                            :label="__('End of')"
                            value="0"
                            x-model="newFilterCalculation.is_start_of"
                        />
                    </div>
                    <div
                        class="flex-1"
                        x-cloak
                        x-show="newFilterCalculation.is_start_of?.length > 0"
                    >
                        <x-select.styled
                            x-model="newFilterCalculation.start_of"
                            :options="[
                                [
                                    'label' => __('Minute'),
                                    'value' => 'minute',
                                ],
                                [
                                    'label' => __('Hour'),
                                    'value' => 'hour',
                                ],
                                [
                                    'label' => __('Day'),
                                    'value' => 'day',
                                ],
                                [
                                    'label' => __('Week'),
                                    'value' => 'week',
                                ],
                                [
                                    'label' => __('Month'),
                                    'value' => 'month',
                                ],
                                [
                                    'label' => __('Year'),
                                    'value' => 'year',
                                ],
                            ]"
                        />
                    </div>
                </div>
            </div>
            <x-slot:footer>
                <x-button
                    color="secondary"
                    light
                    flat
                    :text="__('Cancel')"
                    x-on:click="$modalClose('date-calculation')"
                />
                <x-button
                    :text="__('Save')"
                    x-on:click="addCalculation(dateCalculation); $modalClose('date-calculation');"
                />
            </x-slot>
        </x-modal>
    @endif

    <div class="pb-2.5">
        <div class="dark:border-secondary-700 border-b border-gray-200">
            <nav class="soft-scrollbar flex gap-x-8 overflow-x-auto">
                @if ($this->isFilterable)
                    <button
                        wire:loading.attr="disabled"
                        x-on:click.prevent="tab = 'edit-filters'"
                        x-bind:class="{
                            '!border-indigo-500 text-indigo-600': tab === 'edit-filters',
                        }"
                        class="cursor-pointer whitespace-nowrap border-b-2 border-transparent px-1 py-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-50"
                    >
                        {{ __('Filters') }}
                    </button>
                @endif

                @if ($this->aggregatable)
                    <button
                        wire:loading.attr="disabled"
                        x-on:click.prevent="sortCols = enabledCols; tab = 'summarize';"
                        x-bind:class="{ '!border-indigo-500 text-indigo-600': tab === 'summarize' }"
                        class="cursor-pointer whitespace-nowrap border-b-2 border-transparent px-1 py-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-50"
                    >
                        {{ __('Summarize') }}
                    </button>
                @endif

                <button
                    wire:loading.attr="disabled"
                    x-on:click.prevent="sortCols = enabledCols; tab = 'columns';"
                    x-bind:class="{ '!border-indigo-500 text-indigo-600': tab === 'columns' }"
                    class="cursor-pointer whitespace-nowrap border-b-2 border-transparent px-1 py-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-50"
                >
                    {{ __('Columns') }}
                </button>
                @if ($this->isExportable)
                    <button
                        wire:loading.attr="disabled"
                        x-on:click.prevent="
                            getColumns()
                            tab = 'export'
                        "
                        x-bind:class="{ '!border-indigo-500 text-indigo-600': tab === 'export' }"
                        class="cursor-pointer whitespace-nowrap border-b-2 border-transparent px-1 py-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-50"
                    >
                        {{ __('Export') }}
                    </button>
                @endif
            </nav>
        </div>
    </div>
    <div class="relative">
        @if ($this->isFilterable)
            <div x-cloak x-show="tab === 'edit-filters'">
                <form
                    class="grid grid-cols-1 gap-3"
                    x-on:submit.prevent="addFilter()"
                >
                    @if (auth()->user() && method_exists(auth()->user(), 'datatableUserSettings'))
                        <template
                            x-if="$wire.$parent.savedFilters?.length > 0"
                        >
                            <div>
                                <div
                                    class="dark:bg-secondary-800 dark:border-secondary-600 dark:text-secondary-400 border-secondary-300 focus:ring-primary-500 focus:border-primary-500 block flex w-full cursor-pointer justify-between rounded-md border bg-white px-3 py-2 text-base shadow-sm focus:outline-none focus:ring-1 sm:text-sm"
                                    x-on:click="showSavedFilters = ! showSavedFilters"
                                >
                                    <x-label class="mr-2">
                                        {{ __('Saved filters') }}
                                    </x-label>
                                    <x-icon
                                        name="chevron-right"
                                        class="h-4 w-4 transform transition-transform"
                                        x-bind:class="{'rotate-90': showSavedFilters}"
                                    />
                                </div>
                                <div
                                    class="relative py-3"
                                    x-collapse
                                    x-show="showSavedFilters"
                                    x-cloak
                                >
                                    <div
                                        class="grid grid-cols-1 items-center justify-center gap-3"
                                        x-data="{ detail: null }"
                                    >
                                        <template
                                            x-for="(filter, index) in $wire.$parent.savedFilters"
                                        >
                                            <div>
                                                <x-card>
                                                    <x-slot:title>
                                                        <x-input
                                                            x-model="filter.name"
                                                            x-on:input.debounce="$wire.$parent.updateSavedFilter(filter.id, filter)"
                                                        />
                                                    </x-slot>
                                                    <x-slot:header>
                                                        <x-button.circle
                                                            color="red"
                                                            2xs
                                                            icon="x-mark"
                                                            x-on:click="
                                                            savedFilters.splice(savedFilters.indexOf(index), 1);
                                                            $wire.$parent.deleteSavedFilter(filter.id)
                                                        "
                                                        />
                                                    </x-slot>
                                                    <div
                                                        class="flex justify-between"
                                                    >
                                                        <div class="flex gap-1">
                                                            <x-badge
                                                                flat
                                                                color="indigo"
                                                            >
                                                                <x-slot:text>
                                                                    <span
                                                                        x-text="filter.is_permanent ? '{{ __('Permanent') }}' : '{{ __('Temporary') }}'"
                                                                    ></span>
                                                                </x-slot>
                                                            </x-badge>
                                                        </div>
                                                        <div
                                                            class="flex items-center gap-1"
                                                        >
                                                            <x-button
                                                                color="secondary"
                                                                light
                                                                x-cloak
                                                                x-show="filter.settings.enabledCols?.length"
                                                                :text="__('Delete column layout')"
                                                                wire:click="$parent.deleteSavedFilterEnabledCols(filter.id)"
                                                            />
                                                            <x-button
                                                                :text="__('Apply')"
                                                                color="indigo"
                                                                x-on:click="$wire.$parent.loadFilter(filter.settings), detail = null, showSavedFilters = false"
                                                            />
                                                            <x-icon
                                                                name="chevron-left"
                                                                class="h-4 w-4 cursor-pointer"
                                                                x-bind:class="{'-rotate-90': detail === index}"
                                                                x-on:click="detail === index ? detail = null : detail = index"
                                                            />
                                                        </div>
                                                    </div>
                                                    <div
                                                        x-collapse
                                                        x-cloak
                                                        x-show="detail === index"
                                                    >
                                                        <div
                                                            class="flex flex-col items-center justify-center space-y-4"
                                                            x-cloak
                                                            x-show="filter.settings.userFilters.length > 0"
                                                        >
                                                            <template
                                                                x-for="(orFilters, orIndex) in filter.settings.userFilters"
                                                            >
                                                                <div
                                                                    class="flex flex-col items-center justify-center"
                                                                >
                                                                    <div
                                                                        class="flex justify-between"
                                                                    >
                                                                        <div
                                                                            class="flex gap-1 pt-1"
                                                                        >
                                                                            <template
                                                                                x-for="(filter, index) in orFilters"
                                                                            >
                                                                                <div>
                                                                                    <x-badge
                                                                                        flat
                                                                                        color="indigo"
                                                                                    >
                                                                                        <x-slot:text>
                                                                                            <span
                                                                                                x-text="filterBadge(filter)"
                                                                                            ></span>
                                                                                        </x-slot>
                                                                                    </x-badge>
                                                                                    <template
                                                                                        x-if="orFilters.length - 1 !== index"
                                                                                    >
                                                                                        <x-badge
                                                                                            flat
                                                                                            color="red"
                                                                                            :text="__('and')"
                                                                                        />
                                                                                    </template>
                                                                                </div>
                                                                            </template>
                                                                        </div>
                                                                    </div>
                                                                    <template
                                                                        x-if="filter.settings.userFilters.length - 1 !== orIndex"
                                                                    >
                                                                        <div
                                                                            class="pt-3"
                                                                        >
                                                                            <x-badge
                                                                                flat
                                                                                color="emerald"
                                                                                :text="__('or')"
                                                                            />
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <x-badge
                                                                x-cloak
                                                                x-show="filter.settings.orderBy"
                                                                flat
                                                                amber
                                                            >
                                                                <x-slot:text>
                                                                    <span>
                                                                        {{ __('Order by') }}
                                                                    </span>
                                                                    <span
                                                                        x-text="filter.settings.orderBy"
                                                                    ></span>
                                                                    <span
                                                                        x-text="filter.settings.orderAsc ? '{{ __('asc') }}' : '{{ __('desc') }}'"
                                                                    ></span>
                                                                </x-slot>
                                                            </x-badge>
                                                        </div>
                                                    </div>
                                                </x-card>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    @endif

                    <x-select.native
                        name="new-filter-relation"
                        wire:target="loadFields"
                        wire:loading.attr="disabled"
                        x-model="newFilter.relation"
                    >
                        <option value="0">{{ __('This table') }}</option>
                        <template
                            x-for="relation in $wire.$parent.selectedRelations"
                        >
                            <option
                                x-bind:value="relation.name"
                                x-text="relation.label"
                            ></option>
                        </template>
                    </x-select.native>
                    <x-input
                        name="new-filter-column"
                        wire:target="loadFields"
                        wire:loading.attr="disabled"
                        x-ref="filterColumn"
                        required
                        x-model.lazy="newFilter.column"
                        placeholder="{{ __('Column') }}"
                        x-bind:list="$id('enabledCols')"
                    />
                    <datalist x-bind:id="$id('enabledCols')">
                        <template
                            x-for="
                                col in
                                    relationTableFields[newFilter.relation === '' ? 'self' : newFilter.relation]
                            "
                        >
                            <option
                                x-bind:value="col"
                                x-text="getLabel(col)"
                            ></option>
                        </template>
                    </datalist>
                    <div
                        x-cloak
                        x-show="filterSelectType !== 'valueList' && filterSelectType !== 'search'"
                    >
                        <x-input
                            name="new-filter-operator"
                            x-ref="filterOperator"
                            x-model="newFilter.operator"
                            placeholder="{{ __('Operator') }}"
                            x-bind:list="$id('operators')"
                        />
                        <datalist x-bind:id="$id('operators')">
                            <option value="=">{{ __('=') }}</option>
                            <option value="!=">{{ __('!=') }}</option>
                            <option value=">">{{ __('>') }}</option>
                            <option value=">=">{{ __('>=') }}</option>
                            <option value="<">{{ __('<') }}</option>
                            <option value="<=">{{ __('<=') }}</option>
                            <option value="like">{{ __('like') }}</option>
                            <option value="not like">
                                {{ __('not like') }}
                            </option>
                            <option value="is null">
                                {{ __('is null') }}
                            </option>
                            <option value="is not null">
                                {{ __('is not null') }}
                            </option>
                            <option value="between">
                                {{ __('between') }}
                            </option>
                        </datalist>
                    </div>
                    <div x-cloak x-show="filterSelectType === 'valueList'">
                        <x-select.native
                            name="new-filter-value-select"
                            x-model="newFilter.value"
                            placeholder="{{ __('Value') }}"
                        >
                            <option value=""></option>
                            <template
                                x-for="item in filterValueLists[newFilter.column]"
                            >
                                <option
                                    x-bind:value="item.value"
                                    x-text="item.label"
                                ></option>
                            </template>
                        </x-select.native>
                    </div>
                    <div
                        x-cloak
                        x-show="filterSelectType === 'text'"
                        class="flex flex-col gap-1.5"
                    >
                        <div class="flex items-center gap-1.5">
                            <x-input
                                name="new-filter-value"
                                x-show="! newFilter.value[0]?.hasOwnProperty('calculation')"
                                x-bind:type="getFilterInputType(newFilter.relation + '.' + newFilter.column)"
                                x-model="newFilter.value[0]"
                                placeholder="{{ __('Value') }}"
                                x-ref="filterValue"
                            />
                            <div
                                class="flex"
                                x-cloak
                                x-show="newFilter.value[0]?.hasOwnProperty('calculation')"
                            >
                                <x-badge
                                    color="indigo"
                                    x-text="getCalculationLabel(newFilter.value[0]?.calculation)"
                                ></x-badge>
                            </div>
                            <div
                                x-cloak
                                x-show="
                                    getFilterInputType(newFilter.relation + '.' + newFilter.column).startsWith(
                                        'date',
                                    )
                                "
                            >
                                <x-button
                                    color="secondary"
                                    light
                                    icon="calculator"
                                    class="w-full"
                                    x-on:click="dateCalculation = 0; $modalOpen('date-calculation');"
                                ></x-button>
                            </div>
                        </div>
                        <div
                            x-cloak
                            x-show="newFilter.operator === 'between'"
                        >
                            <x-label class="text-center" :label="__('and')" />
                            <div class="flex items-center gap-1.5">
                                <x-input
                                    name="new-filter-value-2"
                                    x-cloak
                                    x-show="! newFilter.value[1]?.hasOwnProperty('calculation')"
                                    x-bind:type="getFilterInputType(newFilter.relation + '.' + newFilter.column)"
                                    x-model="newFilter.value[1]"
                                    placeholder="{{ __('Value') }}"
                                    x-ref="filterValue"
                                />
                                <div
                                    class="flex"
                                    x-show="newFilter.value[1]?.hasOwnProperty('calculation')"
                                >
                                    <x-badge
                                        color="indigo"
                                        x-text="getCalculationLabel(newFilter.value[1]?.calculation)"
                                    ></x-badge>
                                </div>
                                <div
                                    x-cloak
                                    x-show="
                                        getFilterInputType(newFilter.relation + '.' + newFilter.column).startsWith(
                                            'date',
                                        )
                                    "
                                >
                                    <x-button
                                        color="secondary"
                                        light
                                        icon="calculator"
                                        class="w-full"
                                        x-on:click="dateCalculation = 1; $modalOpen('date-calculation');"
                                    ></x-button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        x-cloak
                        x-show="newFilter.operator === 'like' || newFilter.operator === 'not like'"
                        class="break-long-words max-w-md text-xs text-slate-400"
                    >
                        {{ __('When using the like or not like filter, you can use the % sign as a placeholder. Examples: "test%" for values that start with "test", "%test" for values that end with "test", and "%test%" for values that contain "test" anywhere.') }}
                    </div>
                    <x-checkbox
                        x-model="$wire.$parent.withSoftDeletes"
                        x-on:change="$wire.$parent.$call('startSearch')"
                        :label="__('Include deleted')"
                    />
                    <x-button
                        wire:target="loadFields"
                        wire:loading.attr="disabled"
                        type="submit"
                        x-ref="filterAddButton"
                        color="indigo"
                    >
                        {{ __('Add filter') }}
                    </x-button>
                </form>
                <div
                    class="flex flex-col items-center justify-center space-y-4"
                    x-cloak
                    x-show="filters.length > 0 || orderByCol"
                >
                    <template x-for="(orFilters, orIndex) in filters">
                        <div class="flex flex-col items-center justify-center">
                            <div
                                x-on:click="filterIndex = orIndex"
                                x-bind:class="filterIndex === orIndex ? 'ring-2 ring-indigo-600' : 'ring-1 ring-slate-700/10'"
                                class="pr-6.5 dark:bg-secondary-800 pointer-events-auto relative w-full rounded-lg bg-white p-4 text-sm leading-5 shadow-xl shadow-black/5 hover:bg-slate-50"
                            >
                                <div class="absolute right-0.5 top-0.5">
                                    <x-button.circle
                                        color="red"
                                        sm
                                        icon="x-mark"
                                        x-on:click="removeFilterGroup(orIndex)"
                                    />
                                </div>
                                <div class="flex justify-between">
                                    <div class="flex gap-1 pt-1">
                                        <template
                                            x-for="(filter, index) in orFilters"
                                        >
                                            <div>
                                                <x-badge flat color="indigo">
                                                    <x-slot:text>
                                                        <span
                                                            x-text="filterBadge(filter)"
                                                        ></span>
                                                    </x-slot>
                                                    <x-slot
                                                        name="right"
                                                        class="relative flex h-2 w-2 items-center"
                                                    >
                                                        <button
                                                            type="button"
                                                            x-on:click="removeFilter(index, orIndex)"
                                                        >
                                                            <x-icon
                                                                name="x-mark"
                                                                class="h-4 w-4"
                                                            />
                                                        </button>
                                                    </x-slot>
                                                </x-badge>
                                                <template
                                                    x-if="orFilters.length - 1 !== index"
                                                >
                                                    <x-badge
                                                        flat
                                                        color="red"
                                                        :text="__('and')"
                                                    />
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <template
                                x-if="filters.length - 1 !== orIndex"
                            >
                                <div class="pt-3">
                                    <x-badge
                                        flat
                                        color="emerald"
                                        :text="__('or')"
                                    />
                                </div>
                            </template>
                        </div>
                    </template>
                    <div x-cloak x-show="orderByCol">
                        <x-badge flat color="amber">
                            <x-slot:text>
                                <span>{{ __('Order by') }}</span>
                                <span x-text="getLabel(orderByCol)"></span>
                                <span
                                    x-text="orderAsc ? '{{ __('asc') }}' : '{{ __('desc') }}'"
                                ></span>
                            </x-slot>
                            <x-slot
                                name="right"
                                class="relative flex h-2 w-2 items-center"
                            >
                                <button
                                    type="button"
                                    x-on:click="$wire.$parent.sortTable('')"
                                >
                                    <x-icon name="x-mark" class="h-4 w-4" />
                                </button>
                            </x-slot>
                        </x-badge>
                    </div>
                    <x-button
                        color="secondary"
                        light
                        x-cloak
                        x-show="filters.length > 0"
                        color="emerald"
                        :text="__('Add or')"
                        x-on:click="addOrFilter()"
                    />
                    @if (auth()->user() && method_exists(auth()->user(), 'datatableUserSettings'))
                        <x-button
                            color="indigo"
                            class="w-full"
                            x-on:click="$modalOpen('save-filter')"
                        >
                            {{ __('Save') }}
                        </x-button>
                    @endif
                </div>
            </div>
        @endif

        @if ($this->aggregatable)
            <div x-cloak x-show="tab === 'summarize'">
                <div class="pb-2">
                    <x-input
                        type="search"
                        x-model.debounce.300ms="searchAggregatable"
                        placeholder="{{ __('Search') }}"
                        class="w-full"
                    />
                </div>
                <div class="grid grid-cols-1 gap-3">
                    <template
                        x-for="col in searchable(aggregatable, searchAggregatable)"
                    >
                        <div>
                            <x-label>
                                <span x-text="getLabel(col)"></span>
                            </x-label>
                            <x-checkbox
                                sm
                                :label="__('Sum')"
                                x-bind:value="col"
                                x-model="aggregatableCols.sum"
                            />
                            <x-checkbox
                                sm
                                :label="__('Average')"
                                x-bind:value="col"
                                x-model="aggregatableCols.avg"
                            />
                            <x-checkbox
                                sm
                                :label="__('Minimum')"
                                x-bind:value="col"
                                x-model="aggregatableCols.min"
                            />
                            <x-checkbox
                                sm
                                :label="__('Maximum')"
                                x-bind:value="col"
                                x-model="aggregatableCols.max"
                            />
                        </div>
                    </template>
                </div>
            </div>
        @endif

        <div x-cloak x-show="tab === 'columns'">
            <div
                x-data="{
                    attributes: [],
                    availableCols: [...$wire.$parent.enabledCols, ...['__placeholder__']],
                    addCol(colName) {
                        if (this.availableCols.includes(colName))
                            this.availableCols.splice(this.availableCols.indexOf(colName), 1)
                        else {
                            this.availableCols.push(colName)
                        }
                    },
                }"
            >
                <div
                    class="table-cols"
                    x-sort="columnSortHandle($item, $position)"
                >
                    <template x-for="col in availableCols">
                        <div
                            x-sort:item="col"
                            x-bind:data-column="col"
                            x-cloak
                            x-show="col !== '__placeholder__'"
                        >
                            <label
                                x-bind:for="col"
                                class="flex items-center"
                            >
                                <div class="relative flex items-start">
                                    <div class="flex h-5 items-center">
                                        <x-checkbox
                                            sm
                                            x-bind:id="col"
                                            x-bind:value="col"
                                            x-model="enabledCols"
                                            wire:loading.attr="disabled"
                                        >
                                            <x-slot:label>
                                                <span
                                                    x-text="getLabel(col)"
                                                />
                                            </x-slot>
                                        </x-checkbox>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </template>
                </div>
                <div class="flex justify-end pt-2">
                    <x-button
                        color="secondary"
                        light
                        x-on:click="resetLayout"
                        :text="__('Reset Layout')"
                    />
                </div>
                <div
                    class="text-sm font-medium text-gray-700 dark:text-gray-50"
                >
                    <div class="flex overflow-x-auto">
                        <div class="flex items-center gap-1.5">
                            <x-button
                                flat
                                color="indigo"
                                x-on:click="searchRelations = null; searchColumns = null; $wire.$parent.loadSlug()"
                            >
                                <span class="whitespace-nowrap">
                                    {{ __('This table') }}
                                </span>
                            </x-button>
                            <x-icon name="chevron-right" class="h-4 w-4" />
                        </div>
                        <template
                            x-for="segment in $wire.$parent.displayPath"
                        >
                            <div class="flex items-center gap-1.5">
                                <x-button
                                    flat
                                    color="indigo"
                                    x-on:click="searchRelations = null; searchColumns = null; $wire.$parent.loadSlug(segment.value)"
                                >
                                    <span
                                        class="whitespace-nowrap"
                                        x-text="segment.label"
                                    ></span>
                                </x-button>
                                <x-icon name="chevron-right" class="h-4 w-4" />
                            </div>
                        </template>
                    </div>
                    <hr class="pb-2.5" />
                    <div class="grid grid-cols-2 gap-1.5">
                        <div>
                            <div class="pb-2">
                                <x-input
                                    type="search"
                                    x-model.debounce.300ms="searchColumns"
                                    placeholder="{{ __('Search') }}"
                                    class="w-full"
                                />
                            </div>
                            <template
                                x-for="col in searchable($wire.$parent.selectedCols, searchColumns)"
                            >
                                <div class="flex gap-1.5">
                                    <x-checkbox
                                        sm
                                        x-bind:checked="$wire.$parent.enabledCols.includes(col.attribute)"
                                        wire:loading.attr="disabled"
                                        x-bind:id="col.attribute"
                                        x-bind:value="col.attribute"
                                        x-on:change="loadFilterable; addCol(col.attribute);"
                                        x-model="enabledCols"
                                    >
                                        <x-slot:label>
                                            <span
                                                class="overflow-hidden text-ellipsis whitespace-nowrap"
                                                x-text="col.label"
                                            ></span>
                                        </x-slot>
                                    </x-checkbox>
                                </div>
                            </template>
                        </div>
                        <div>
                            <div class="pb-2">
                                <x-input
                                    type="search"
                                    x-model.debounce.300ms="searchRelations"
                                    placeholder="{{ __('Search') }}"
                                    class="w-full"
                                />
                            </div>
                            <template
                                x-for="relation in searchable($wire.$parent.selectedRelations, searchRelations)"
                            >
                                <div
                                    class="flex cursor-pointer items-center gap-1.5"
                                    x-on:click="
                                        searchRelations = null
                                        searchColumns = null
                                        $wire.$parent.loadRelation(relation.model, relation.name)
                                    "
                                >
                                    <span
                                        class="overflow-hidden text-ellipsis whitespace-nowrap"
                                        x-text="relation.label"
                                    ></span>
                                    <x-icon
                                        name="chevron-right"
                                        class="h-4 w-4"
                                    />
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if ($this->isExportable)
            <div x-cloak x-show="tab === 'export'">
                <template x-for="columnName in exportableColumns">
                    <div>
                        <label for="" class="flex items-center">
                            <div class="relative flex items-start">
                                <div class="flex h-5 items-center">
                                    <input
                                        type="checkbox"
                                        class="border-secondary-300 text-primary-600 focus:ring-primary-600 focus:border-primary-400 dark:border-secondary-500 dark:checked:border-secondary-600 dark:focus:ring-secondary-600 dark:focus:border-secondary-500 dark:bg-secondary-600 dark:text-secondary-600 dark:focus:ring-offset-secondary-800 form-checkbox rounded transition duration-100 ease-in-out"
                                        x-bind:value="columnName"
                                        x-model="exportColumns"
                                        value="uuid"
                                    />
                                </div>
                                <div class="ml-2 text-sm">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-400"
                                    >
                                        <span x-text="columnName"></span>
                                    </label>
                                </div>
                            </div>
                        </label>
                    </div>
                </template>
                <div class="pt-3">
                    <x-button
                        loading
                        x-on:click="$wire.$parent.export(exportColumns)"
                        color="indigo"
                        class="w-full"
                    >
                        {{ __('Export') }}
                    </x-button>
                </div>
            </div>
        @endif
    </div>
</div>
