<div
    class="mt-2 px-1"
    x-data="datatableOptions($wire)"
    x-init="
        aggregatable = {{ Js::from($this->getAggregatable()) }};
        groupable = {{ Js::from($this->getGroupableCols()) }};
        operatorLabels = {{ Js::from($this->getOperatorLabels()) }};
    "
>
    @if (auth()->user() && method_exists(auth()->user(), 'datatableUserSettings'))
        <x-modal
            persistent
            id="save-filter"
            :title="__('Save filter')"
            x-on:close="filterName = ''; permanent = false;"
            x-on:open="$focusOn('filter-name')"
        >
            <x-input
                required
                id="filter-name"
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
                    x-on:click="$tsui.close.modal('save-filter')"
                />
                <x-button
                    :text="__('Save')"
                    x-on:click="$wire.saveFilter(filterName, permanent, withEnabledCols).then(() => $tsui.close.modal('save-filter'));"
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
                        text="-"
                    />
                    <x-button
                        x-bind:class="newFilterCalculation.operator === '+' && 'ring-2 ring-offset-2'"
                        x-on:click="newFilterCalculation.operator = '+'"
                        color="emerald"
                        text="+"
                    />
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
                    x-on:click="$tsui.close.modal('date-calculation')"
                />
                <x-button
                    :text="__('Save')"
                    x-on:click="addCalculation(dateCalculation); $tsui.close.modal('date-calculation');"
                />
            </x-slot>
        </x-modal>
    @endif

    <x-tab
        :selected="$this->isFilterable ? 'edit-filters' : 'columns'"
        scroll-on-mobile
        x-on:navigate="handleTabNavigate($event.detail.select)"
    >
        @if ($this->isFilterable)
            <x-tab.items tab="edit-filters" :title="__('Filters')">
                <form
                    class="grid grid-cols-1 gap-2"
                    x-on:submit.prevent="addFilter()"
                >
                    @if (auth()->user() && method_exists(auth()->user(), 'datatableUserSettings'))
                        <template
                            x-if="$wire.savedFilters?.length > 0"
                        >
                            <div>
                                <div
                                    class="dark:bg-secondary-800 dark:border-secondary-700 border-gray-200 block flex w-full cursor-pointer justify-between rounded-md border px-3 py-2 text-sm shadow-sm"
                                    x-on:click="showSavedFilters = ! showSavedFilters"
                                >
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ __('Saved filters') }}
                                    </span>
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
                                            x-for="(filter, index) in $wire.savedFilters"
                                        >
                                            <div>
                                                <x-card>
                                                    <x-slot:header>
                                                        <div class="flex gap-2 w-full">
                                                            <x-input
                                                                x-model="filter.name"
                                                                x-on:input.debounce="$wire.updateSavedFilter(filter.id, filter)"
                                                            />
                                                            <x-button.circle
                                                                color="red"
                                                                2xs
                                                                icon="x-mark"
                                                                x-on:click="
                                                                    savedFilters.splice(savedFilters.indexOf(index), 1);
                                                                    $wire.deleteSavedFilter(filter.id)
                                                                "
                                                            />
                                                        </div>
                                                    </x-slot>
                                                    <div
                                                        class="flex justify-between text-sm"
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
                                                                x-on:click="$wire.loadFilter(filter.settings), detail = null, showSavedFilters = false"
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
                                                                    <span>{{ __('Order by') }}</span>
                                                                    &nbsp;
                                                                    <span
                                                                        x-text="filter.settings.orderBy"
                                                                    ></span>
                                                                    &nbsp;
                                                                    <span
                                                                        x-text="filter.settings.orderAsc ? '{{ __('asc') }}' : '{{ __('desc') }}'"
                                                                    ></span>
                                                                </x-slot>
                                                            </x-badge>
                                                            <x-badge
                                                                x-cloak
                                                                x-show="filter.settings.groupBy"
                                                                flat
                                                                cyan
                                                            >
                                                                <x-slot:text>
                                                                    <span>{{ __('Grouped by') }}</span>
                                                                    &nbsp;
                                                                    <span
                                                                        x-text="filter.settings.groupBy"
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
                            x-for="relation in selectedRelations"
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
                        :list="'filter-cols-' . strtolower($this->getId())"
                    />
                    <datalist id="filter-cols-{{ strtolower($this->getId()) }}">
                        <template
                            x-for="
                                col in
                                    relationTableFields[!newFilter.relation || newFilter.relation === '0' ? 'self' : newFilter.relation]
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
                        x-show="filterSelectType !== 'valueList' && filterSelectType !== 'search' && filterSelectType !== 'none'"
                    >
                        <x-input
                            name="new-filter-operator"
                            x-ref="filterOperator"
                            x-model="newFilter.operator"
                            placeholder="{{ __('Operator') }}"
                            list="filter-operators-{{ strtolower($this->getId()) }}"
                        />
                        <datalist id="filter-operators-{{ strtolower($this->getId()) }}">
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
                    <div
                        x-cloak
                        x-show="filterSelectType === 'valueList' || filterSelectType === 'none'"
                    >
                        <x-select.native
                            name="new-filter-operator-valuelist"
                            x-model="newFilter.operator"
                            placeholder="{{ __('Operator') }}"
                        >
                            <option value="=">{{ __('=') }}</option>
                            <option value="!=">{{ __('!=') }}</option>
                            <option value="is null">
                                {{ __('is null') }}
                            </option>
                            <option value="is not null">
                                {{ __('is not null') }}
                            </option>
                        </x-select.native>
                    </div>
                    <div class="w-full" x-cloak x-show="filterSelectType === 'valueList'">
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
                        class="flex w-full flex-col gap-1.5"
                    >
                        <div class="w-full">
                            <x-input
                                name="new-filter-value"
                                x-show="! newFilter.value[0]?.hasOwnProperty('calculation')"
                                x-bind:type="getFilterInputType(newFilter.relation + '.' + newFilter.column)"
                                x-model="newFilter.value[0]"
                                placeholder="{{ __('Value') }}"
                                x-ref="filterValue"
                            />
                            <div
                                class="flex shrink-0"
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
                                    x-on:click="dateCalculation = 0; $tsui.open.modal('date-calculation');"
                                ></x-button>
                            </div>
                        </div>
                        <div
                            x-cloak
                            x-show="newFilter.operator === 'between'"
                        >
                            <span class="block text-center text-xs text-gray-400 dark:text-gray-500">{{ __('and') }}</span>
                            <div class="flex items-center gap-1.5">
                                <x-input
                                    name="new-filter-value-2"
                                    class="w-full"
                                    x-cloak
                                    x-show="! newFilter.value[1]?.hasOwnProperty('calculation')"
                                    x-bind:type="getFilterInputType(newFilter.relation + '.' + newFilter.column)"
                                    x-model="newFilter.value[1]"
                                    placeholder="{{ __('Value') }}"
                                    x-ref="filterValue"
                                />
                                <div
                                    class="flex shrink-0"
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
                                        x-on:click="dateCalculation = 1; $tsui.open.modal('date-calculation');"
                                    ></x-button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        x-cloak
                        x-show="newFilter.operator === 'like' || newFilter.operator === 'not like'"
                        class="break-long-words max-w-md text-xs text-gray-400 dark:text-gray-500"
                    >
                        {{ __('When using the like or not like filter, you can use the % sign as a placeholder. Examples: "test%" for values that start with "test", "%test" for values that end with "test", and "%test%" for values that contain "test" anywhere.') }}
                    </div>
                    <div class="py-1">
                        <x-checkbox
                            x-model="$wire.withSoftDeletes"
                            x-on:change="$wire.startSearch()"
                            :label="__('Include deleted')"
                        />
                    </div>
                    <x-button
                        wire:target="loadFields"
                        wire:loading.attr="disabled"
                        type="submit"
                        x-ref="filterAddButton"
                        color="indigo"
                        :text="__('Add filter')"
                    />
                </form>
                <div
                    class="border-t border-gray-200 pt-4 dark:border-secondary-700"
                    x-cloak
                    x-show="filters.length > 0 || orderByCol || groupBy"
                >
                <div class="flex flex-col items-center justify-center space-y-3">
                    <template x-for="(orFilters, orIndex) in filters">
                        <div class="flex flex-col items-center justify-center">
                            <div
                                x-on:click="filterIndex = orIndex"
                                x-bind:class="filterIndex === orIndex ? 'ring-2 ring-indigo-600' : 'ring-1 ring-slate-700/10'"
                                class="dark:bg-secondary-800 pointer-events-auto relative w-full rounded-lg bg-white p-2.5 pr-6 text-sm leading-5 shadow-md shadow-black/5 hover:bg-gray-100 dark:hover:bg-secondary-900"
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
                                                        <x-button.circle
                                                            2xs
                                                            flat
                                                            icon="x-mark"
                                                            x-on:click="removeFilter(index, orIndex)"
                                                        />
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
                            <template x-if="filters.length - 1 !== orIndex">
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
                                &nbsp;
                                <span x-text="getLabel(orderByCol)"></span>
                                &nbsp;
                                <span
                                    x-text="orderAsc ? '{{ __('asc') }}' : '{{ __('desc') }}'"
                                ></span>
                            </x-slot>
                            <x-slot
                                name="right"
                                class="relative flex h-2 w-2 items-center"
                            >
                                <x-button.circle
                                    2xs
                                    flat
                                    icon="x-mark"
                                    x-on:click="$wire.sortTable('')"
                                />
                            </x-slot>
                        </x-badge>
                    </div>
                    <div x-cloak x-show="groupBy">
                        <x-badge flat color="cyan">
                            <x-slot:text>
                                <span>{{ __('Grouped by') }}</span>
                                &nbsp;
                                <span x-text="getLabel(groupBy)"></span>
                            </x-slot>
                            <x-slot
                                name="right"
                                class="relative flex h-2 w-2 items-center"
                            >
                                <x-button.circle
                                    2xs
                                    flat
                                    icon="x-mark"
                                    x-on:click="groupBy = null; $wire.setGroupBy(null)"
                                />
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
                            x-on:click="$tsui.open.modal('save-filter')"
                            :text="__('Save')"
                        />
                    @endif
                </div>
                </div>
            </x-tab.items>
        @endif

        <x-tab.items tab="columns" :title="__('Columns')">
            <div
                x-data="{
                    attributes: [],
                    availableCols: [...$wire.enabledCols, ...['__placeholder__']],
                    addCol(colName) {
                        if (this.availableCols.includes(colName))
                            this.availableCols.splice(this.availableCols.indexOf(colName), 1)
                        else {
                            this.availableCols.push(colName)
                        }
                    },
                }"
            >
                <div class="border-b border-gray-200 pb-2 dark:border-secondary-700">
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
                            class="min-w-0 py-1"
                        >
                            <label
                                x-bind:for="col"
                                class="flex min-w-0 items-center"
                            >
                                <div class="relative flex min-w-0 items-start">
                                    <div class="flex h-5 min-w-0 items-center">
                                        <x-checkbox
                                            sm
                                            x-bind:id="col"
                                            x-bind:value="col"
                                            x-model="enabledCols"
                                            wire:loading.attr="disabled"
                                        >
                                            <x-slot:label>
                                                <span
                                                    class="block truncate text-sm text-gray-700 dark:text-gray-300"
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
                <div class="flex justify-start pt-2">
                    <x-button
                        color="secondary"
                        light
                        loading="resetLayout"
                        x-on:click="resetLayout"
                        :text="__('Reset Layout')"
                    />
                </div>
                </div>
                <div
                    class="pt-3 text-sm font-medium text-gray-700 dark:text-gray-50"
                >
                    <nav class="flex items-center overflow-x-auto" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-1 text-sm">
                            <li>
                                <x-button
                                    xs
                                    flat
                                    color="primary"
                                    :text="__('This table')"
                                    x-on:click="
                                        searchRelations = null;
                                        searchColumns = null;
                                        const d = await $wire.loadSlug();
                                        selectedCols = d?.cols || [];
                                        selectedRelations = d?.relations || [];
                                        displayPath = d?.displayPath || [];
                                    "
                                />
                            </li>
                            <template x-for="segment in displayPath">
                                <li class="flex items-center gap-1">
                                    <x-icon name="chevron-right" class="h-3 w-3 text-gray-400" />
                                    <x-button
                                        xs
                                        flat
                                        color="primary"
                                        x-on:click="
                                            searchRelations = null;
                                            searchColumns = null;
                                            const d = await $wire.loadSlug(segment.value);
                                            selectedCols = d?.cols || [];
                                            selectedRelations = d?.relations || [];
                                            displayPath = d?.displayPath || [];
                                        "
                                    >
                                        <span x-text="segment.label"></span>
                                    </x-button>
                                </li>
                            </template>
                        </ol>
                    </nav>
                    <hr class="mt-2 border-gray-200 pb-2.5 dark:border-secondary-700" />
                    <div class="grid grid-cols-2 gap-1.5 overflow-hidden">
                        <div class="min-w-0 overflow-hidden">
                            <span class="mb-1 block text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400">{{ __('Columns') }}</span>
                            <div class="pb-2">
                                <x-input
                                    type="search"
                                    x-model.debounce.300ms="searchColumns"
                                    placeholder="{{ __('Search') }}"
                                    class="w-full"
                                />
                            </div>
                            <template
                                x-for="col in searchable(selectedCols, searchColumns)"
                            >
                                <label class="flex min-w-0 cursor-pointer items-center gap-1.5 overflow-hidden py-1">
                                    <x-checkbox
                                        sm
                                        x-bind:checked="$wire.enabledCols.includes(col.attribute)"
                                        wire:loading.attr="disabled"
                                        x-bind:id="col.attribute"
                                        x-bind:value="col.attribute"
                                        x-on:change="loadFilterable; addCol(col.attribute);"
                                        x-model="enabledCols"
                                    />
                                    <span
                                        class="truncate text-sm text-gray-700 dark:text-gray-300"
                                        x-text="col.label"
                                    ></span>
                                </label>
                            </template>
                        </div>
                        <div class="min-w-0 overflow-hidden">
                            <span class="mb-1 block text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400">{{ __('Relations') }}</span>
                            <div class="pb-2">
                                <x-input
                                    type="search"
                                    x-model.debounce.300ms="searchRelations"
                                    placeholder="{{ __('Search') }}"
                                    class="w-full"
                                />
                            </div>
                            <template
                                x-for="relation in searchable(selectedRelations, searchRelations)"
                            >
                                <div
                                    class="flex min-w-0 cursor-pointer items-center gap-1.5 overflow-hidden py-1"
                                    x-on:click="
                                        searchRelations = null;
                                        searchColumns = null;
                                        const data = await $wire.loadRelation(relation.model, relation.name);
                                        selectedCols = data?.cols || [];
                                        selectedRelations = data?.relations || [];
                                        displayPath = data?.displayPath || [];
                                    "
                                >
                                    <span
                                        class="truncate text-sm text-gray-700 dark:text-gray-300"
                                        x-text="relation.label"
                                    ></span>
                                    <x-icon
                                        name="chevron-right"
                                        class="h-4 w-4 shrink-0"
                                    />
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </x-tab.items>

        @if ($this->aggregatable)
            <x-tab.items tab="summarize" :title="__('Summarize')">
                <div class="pb-2">
                    <x-input
                        type="search"
                        x-model.debounce.300ms="searchAggregatable"
                        placeholder="{{ __('Search') }}"
                        class="w-full"
                    />
                </div>
                <div class="grid grid-cols-1 gap-2">
                    <template
                        x-for="col in searchable(aggregatable, searchAggregatable)"
                    >
                        <div class="pt-3 border-t border-gray-200 dark:border-secondary-700">
                            <span class="mb-1 block text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400" x-text="getLabel(col)"></span>
                            <div class="py-1">
                                <x-checkbox
                                    sm
                                    :label="__('Sum')"
                                    x-bind:value="col"
                                    x-model="aggregatableCols.sum"
                                />
                            </div>
                            <div class="py-1">
                                <x-checkbox
                                    sm
                                    :label="__('Average')"
                                    x-bind:value="col"
                                    x-model="aggregatableCols.avg"
                                />
                            </div>
                            <div class="py-1">
                                <x-checkbox
                                    sm
                                    :label="__('Minimum')"
                                    x-bind:value="col"
                                    x-model="aggregatableCols.min"
                                />
                            </div>
                            <div class="py-1">
                                <x-checkbox
                                    sm
                                    :label="__('Maximum')"
                                    x-bind:value="col"
                                    x-model="aggregatableCols.max"
                                />
                            </div>
                        </div>
                    </template>
                </div>
            </x-tab.items>
        @endif

        <x-tab.items tab="grouping" :title="__('Group')">
            <div class="mb-3 pb-3 border-b border-gray-200 dark:border-secondary-700" x-show="groupBy" x-cloak>
                <span class="mb-2 block text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400">{{ __('Rows per group') }}</span>
                <x-select.native
                    x-model="$wire.groupPerPage"
                    x-on:change="$wire.loadData()"
                >
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </x-select.native>
            </div>
            <div class="pb-2">
                <x-input
                    type="search"
                    x-model.debounce.300ms="searchGroupable"
                    placeholder="{{ __('Search') }}"
                    class="w-full"
                />
            </div>
            <div class="space-y-2">
                <div
                    class="flex items-center justify-between rounded-lg border p-2.5 text-sm"
                    x-bind:class="! groupBy ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-secondary-700'"
                >
                    <x-radio
                        name="groupBy"
                        :label="__('No grouping')"
                        value=""
                        x-bind:checked="! groupBy"
                        x-on:change="groupBy = null; $wire.setGroupBy(null)"
                    />
                    <x-icon
                        name="view-columns"
                        class="h-5 w-5 text-gray-400"
                        x-bind:class="! groupBy && 'text-primary-500'"
                    />
                </div>
                <template x-for="col in searchable(groupable, searchGroupable)">
                    <x-radio
                        name="groupBy"
                        x-bind:value="col"
                        x-bind:checked="groupBy === col"
                        x-on:change="groupBy = col; $wire.setGroupBy(col)"
                    >
                        <x-slot:label>
                            <span x-text="getLabel(col)"></span>
                        </x-slot:label>
                    </x-radio>
                </template>
            </div>
        </x-tab.items>

        @if ($this->isExportable)
            <x-tab.items tab="export" :title="__('Export')">
                @foreach ($this->enabledCols as $col)
                    <div class="py-1">
                        <x-checkbox
                            sm
                            value="{{ $col }}"
                            x-model="exportColumns"
                            :label="$this->colLabels[$col] ?? \Illuminate\Support\Str::headline($col)"
                        />
                    </div>
                @endforeach
                <div class="pt-3 border-t border-gray-200 dark:border-secondary-700">
                    <x-button
                        loading
                        x-on:click="$wire.export(exportColumns); $tsui.close.slide('data-table-sidebar-' + $wire.id.toLowerCase());"
                        color="indigo"
                        class="w-full"
                        :text="__('Export')"
                    />
                </div>
            </x-tab.items>
        @endif
    </x-tab>
</div>
