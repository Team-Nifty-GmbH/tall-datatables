<div
    x-cloak
    x-show="filterIndex > 0 && filterIndex >= ($wire.userFilters || []).length"
    class="mb-2 rounded-md border border-emerald-200 bg-emerald-50/50 px-3 py-2 text-xs text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400"
>
    {{ __('Adding to new OR group') }}
</div>
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
                                        <template x-if="filter.is_own">
                                            <div class="flex gap-2 w-full">
                                                <x-input sm
                                                    x-model="filter.name"
                                                    x-on:input.debounce="$wire.updateSavedFilter(filter.id, filter)"
                                                />
                                                <x-button.circle
                                                    color="red"
                                                    2xs
                                                    icon="x-mark"
                                                    x-on:click="
                                                        savedFilters.splice(index, 1);
                                                        $wire.deleteSavedFilter(filter.id)
                                                    "
                                                />
                                            </div>
                                        </template>
                                        <template x-if="!filter.is_own">
                                            <div class="w-full px-1 text-sm font-medium text-gray-700 dark:text-gray-300" x-text="filter.name"></div>
                                        </template>
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
                                            @if($this->canShareFilters())
                                                <template x-if="filter.is_shared">
                                                    <x-badge
                                                        flat
                                                        color="blue"
                                                        :text="__('Shared')"
                                                        sm
                                                    />
                                                </template>
                                            @endif
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
        sm
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
    <x-input sm
        sm
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
        <x-input sm
            sm
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
            <option value="starts with">
                {{ __('starts with') }}
            </option>
            <option value="ends with">
                {{ __('ends with') }}
            </option>
            <option value="contains">
                {{ __('contains') }}
            </option>
            <option value="does not contain">
                {{ __('does not contain') }}
            </option>
            <option value="in">
                {{ __('in') }}
            </option>
            <option value="not in">
                {{ __('not in') }}
            </option>
        </datalist>
    </div>
    <div
        x-cloak
        x-show="filterSelectType === 'valueList' || filterSelectType === 'none'"
    >
        <x-select.native
            sm
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
            sm
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
        {{-- Date column: preset dropdown --}}
        <div
            x-cloak
            x-show="getFilterInputType(newFilter.relation + '.' + newFilter.column).startsWith('date')"
        >
            <x-select.native
                sm
                name="new-filter-date-preset"
                x-on:change="applyDatePreset($event.target.value)"
                :label="__('Date preset')"
            >
                <option value="">{{ __('Select...') }}</option>
                <optgroup label="{{ __('Current') }}">
                    <option value="today">{{ __('Today') }}</option>
                    <option value="yesterday">{{ __('Yesterday') }}</option>
                    <option value="this_week">{{ __('This week') }}</option>
                    <option value="this_month">{{ __('This month') }}</option>
                    <option value="this_quarter">{{ __('This quarter') }}</option>
                    <option value="this_year">{{ __('This year') }}</option>
                </optgroup>
                <optgroup label="{{ __('Past periods') }}">
                    <option value="last_7_days">{{ __('Last 7 days') }}</option>
                    <option value="last_30_days">{{ __('Last 30 days') }}</option>
                    <option value="last_week">{{ __('Last week') }}</option>
                    <option value="last_month">{{ __('Last month') }}</option>
                    <option value="last_quarter">{{ __('Last quarter') }}</option>
                    <option value="last_year">{{ __('Last year') }}</option>
                </optgroup>
                <option value="custom">{{ __('Custom...') }}</option>
            </x-select.native>

            {{-- Inline custom calculation fields --}}
            <div
                x-cloak
                x-show="datePresetLabel === '' && newFilter.value[0]?.hasOwnProperty('calculation')"
                class="mt-2 flex flex-col gap-2 rounded-md border border-gray-200 p-2 dark:border-secondary-700"
            >
                <div class="flex items-center gap-1.5">
                    <x-button
                        sm
                        x-bind:class="newFilterCalculation.operator === '-' && 'ring-2 ring-offset-2'"
                        x-on:click="newFilterCalculation.operator = '-'"
                        color="red"
                        text="-"
                    />
                    <x-button
                        sm
                        x-bind:class="newFilterCalculation.operator === '+' && 'ring-2 ring-offset-2'"
                        x-on:click="newFilterCalculation.operator = '+'"
                        color="emerald"
                        text="+"
                    />
                    <x-number sm min="0" x-model="newFilterCalculation.value" />
                    <x-select.native sm x-model="newFilterCalculation.unit">
                        <option value="days">{{ __('Days') }}</option>
                        <option value="weeks">{{ __('Weeks') }}</option>
                        <option value="months">{{ __('Months') }}</option>
                        <option value="years">{{ __('Years') }}</option>
                    </x-select.native>
                </div>
                <div class="flex items-center gap-1.5">
                    <x-select.native sm x-model="newFilterCalculation.is_start_of">
                        <option value="">{{ __('Same time') }}</option>
                        <option value="1">{{ __('Start of') }}</option>
                        <option value="0">{{ __('End of') }}</option>
                    </x-select.native>
                    <div
                        x-cloak
                        x-show="newFilterCalculation.is_start_of?.length > 0"
                        class="flex-1"
                    >
                        <x-select.native sm x-model="newFilterCalculation.start_of">
                            <option value="day">{{ __('Day') }}</option>
                            <option value="week">{{ __('Week') }}</option>
                            <option value="month">{{ __('Month') }}</option>
                            <option value="quarter">{{ __('Quarter') }}</option>
                            <option value="year">{{ __('Year') }}</option>
                        </x-select.native>
                    </div>
                </div>
                <x-button
                    sm
                    color="primary"
                    :text="__('Apply')"
                    x-on:click="addCalculation(0); newFilter.operator = 'between';"
                />
            </div>

            {{-- Show selected preset label --}}
            <div
                x-cloak
                x-show="datePresetLabel !== '' && newFilter.value[0]?.hasOwnProperty('calculation')"
                class="mt-1"
            >
                <x-badge
                    color="indigo"
                    x-text="datePresetLabel"
                ></x-badge>
            </div>
        </div>

        {{-- Non-date column: normal text/number input --}}
        <div
            x-cloak
            x-show="! getFilterInputType(newFilter.relation + '.' + newFilter.column).startsWith('date')"
            class="w-full"
        >
            <x-input sm
                sm
                name="new-filter-value"
                x-bind:type="getFilterInputType(newFilter.relation + '.' + newFilter.column)"
                x-model="newFilter.value[0]"
                placeholder="{{ __('Value') }}"
                x-ref="filterValue"
            />
        </div>

        {{-- Non-date between: second value input --}}
        <div
            x-cloak
            x-show="newFilter.operator === 'between' && ! getFilterInputType(newFilter.relation + '.' + newFilter.column).startsWith('date')"
        >
            <span class="block text-center text-xs text-gray-400 dark:text-gray-500">{{ __('and') }}</span>
            <x-input sm
                name="new-filter-value-2"
                class="w-full"
                x-bind:type="getFilterInputType(newFilter.relation + '.' + newFilter.column)"
                x-model="newFilter.value[1]"
                placeholder="{{ __('Value') }}"
            />
        </div>
    </div>
    <div
        x-cloak
        x-show="newFilter.operator === 'like' || newFilter.operator === 'not like'"
        class="break-long-words max-w-md text-xs text-gray-400 dark:text-gray-500"
    >
        {{ __('When using the like or not like filter, you can use the % sign as a placeholder. Examples: "test%" for values that start with "test", "%test" for values that end with "test", and "%test%" for values that contain "test" anywhere.') }}
    </div>
    <div
        x-cloak
        x-show="newFilter.operator === 'in' || newFilter.operator === 'not in'"
        class="break-long-words max-w-md text-xs text-gray-400 dark:text-gray-500"
    >
        {{ __('Separate multiple values with commas. Example: "Berlin, München, Hamburg"') }}
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
        color="primary"
        sm
        :text="__('Add filter')"
    />
</form>
{{-- All active filters (unified) --}}
<div
    class="border-t border-gray-100 pt-3 dark:border-secondary-700/50"
    x-cloak
    x-show="($wire.userFilters || []).length > 0 || orderByCol || groupBy"
>
<div class="flex flex-col gap-1.5">
    <template x-for="(orFilters, orIndex) in ($wire.userFilters || [])">
        <div>
            <div
                class="group/fg flex flex-wrap items-center gap-1.5 rounded px-2 py-1.5 transition-colors"
                x-bind:class="filterIndex === orIndex
                    ? 'bg-gray-100 dark:bg-secondary-700/30'
                    : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-secondary-800'"
                x-on:click="filterIndex = orIndex"
            >
                <template
                    x-for="(filter, index) in orFilters"
                >
                    <div class="flex items-center gap-1.5">
                        <x-badge flat light>
                            <x-slot:text>
                                <span
                                    x-text="filterBadge(filter)"
                                ></span>
                            </x-slot>
                            <x-slot
                                name="right"
                                class="relative flex h-2 w-2 items-center"
                            >
                                <button type="button" class="cursor-pointer" x-on:click.stop="removeFilter(index, orIndex)">
                                    <x-icon name="x-mark" class="h-4 w-4" />
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
                <button
                    type="button"
                    class="ml-auto shrink-0 cursor-pointer rounded p-0.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20"
                    x-on:click.stop="removeFilterGroup(orIndex)"
                >
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>
            <template x-if="($wire.userFilters || []).length - 1 !== orIndex">
                <div class="flex justify-center py-1">
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
    <button
        type="button"
        x-cloak
        x-show="($wire.userFilters || []).length > 0"
        x-on:click="addOrFilter()"
        class="cursor-pointer text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
    >
        {{ __('Add or') }}
    </button>
    @if (auth()->user() && method_exists(auth()->user(), 'datatableUserSettings'))
        <x-button
            color="primary"
            flat
            sm
            x-on:click="$tsui.open.modal('save-filter')"
            :text="__('Save')"
        />
    @endif
</div>
</div>
