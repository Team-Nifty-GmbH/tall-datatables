<x-tall-datatables::sidebar x-on:keydown.esc="showSidebar = false" x-show="showSidebar">
    @if(method_exists(auth()->user(), 'datatableUserSettings'))
        <x-dialog id="save-filter" :title="__('Save filter')">
            <x-input required :label="__('Filter name')" x-model="filterName" />
            <div class="pt-3">
                <x-checkbox :label="__('Permanent')" x-model="permanent" />
            </div>
        </x-dialog>
    @endif
    <div class="mt-2">
        <div class="pb-2.5">
            <div class="border-b border-gray-200 dark:border-secondary-700">
                <nav class="flex gap-x-8 overflow-x-auto soft-scrollbar">
                    @if($this->isFilterable)
                        <button
                            wire:loading.attr="disabled"
                            x-on:click.prevent="tab = 'edit-filters'"
                            x-bind:class="{'!border-indigo-500 text-indigo-600' : tab === 'edit-filters'}"
                            class="cursor-pointer border-transparent text-gray-500 dark:text-gray-50 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        >
                            {{ __('Filters') }}
                        </button>
                    @endif
                    @if($this->aggregatable)
                        <button
                            wire:loading.attr="disabled"
                            x-on:click.prevent="sortCols = cols; tab = 'summarize';"
                            x-bind:class="{'!border-indigo-500 text-indigo-600' : tab === 'summarize'}"
                            class="cursor-pointer border-transparent text-gray-500 dark:text-gray-50 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        >
                            {{ __('Summarize') }}
                        </button>
                    @endif
                    <button
                        wire:loading.attr="disabled"
                        x-on:click.prevent="sortCols = cols; tab = 'columns';"
                        x-bind:class="{'!border-indigo-500 text-indigo-600' : tab === 'columns'}"
                        class="cursor-pointer border-transparent text-gray-500 dark:text-gray-50 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        {{ __('Columns') }}
                    </button>
                    @if($this->isExportable)
                        <button
                            wire:loading.attr="disabled"
                            x-on:click.prevent="getColumns(); tab = 'export'"
                            x-bind:class="{'!border-indigo-500 text-indigo-600' : tab === 'export'}"
                            class="cursor-pointer border-transparent text-gray-500 dark:text-gray-50 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        >
                            {{ __('Export') }}
                        </button>
                    @endif
                </nav>
            </div>
        </div>
        <div class="relative">
            @if($this->isFilterable)
                <div x-cloak x-show="tab === 'edit-filters'">
                    <form class="grid grid-cols-1 gap-3"
                          x-on:submit.prevent="addFilter();">
                        @if(method_exists(auth()->user(), 'datatableUserSettings'))
                            <template x-if="savedFilters?.length > 0">
                                <div>
                                    <div class="flex justify-between block w-full px-3 py-2 text-base sm:text-sm shadow-sm
                                            rounded-md border bg-white focus:ring-1 focus:outline-none cursor-pointer
                                            dark:bg-secondary-800 dark:border-secondary-600 dark:text-secondary-400
                                            border-secondary-300 focus:ring-primary-500 focus:border-primary-500"
                                         x-on:click="showSavedFilters = ! showSavedFilters"
                                    >
                                        <x-label class="mr-2">
                                            {{ __('Saved filters') }}
                                        </x-label>
                                        <x-icon name="chevron-right"
                                            class="transform transition-transform h-4 w-4"
                                            x-bind:class="{'rotate-90': showSavedFilters}"
                                        />
                                    </div>
                                    <div
                                        class="relative py-3"
                                        x-show="showSavedFilters"
                                        x-cloak
                                    >
                                        <x-tall-datatables::spinner />
                                        <div
                                            class="grid grid-cols-1 gap-3 justify-center items-center"
                                            x-data="{detail: null}"
                                        >
                                            <template x-for="(filter, index) in savedFilters">
                                                <x-card>
                                                    <x-slot:title>
                                                        <span x-text="filter.name"></span>
                                                    </x-slot:title>
                                                    <x-slot:action>
                                                        <x-button.circle
                                                            negative
                                                            2xs
                                                            icon="x"
                                                            x-on:click="
                                                                savedFilters.splice(savedFilters.indexOf(index), 1);
                                                                $wire.deleteSavedFilter(filter.id)
                                                            "
                                                        />
                                                    </x-slot:action>
                                                    <div class="flex justify-between">
                                                        <div class="flex gap-1">
                                                            <x-badge flat primary>
                                                                <x-slot:label>
                                                                    <span x-text="filter.is_permanent ? '{{ __('Permanent') }}' : '{{ __('Temporary') }}'"></span>
                                                                </x-slot:label>
                                                            </x-badge>
                                                        </div>
                                                        <div class="flex gap-1 items-center">
                                                            <x-button
                                                                :label="__('Apply')"
                                                                primary
                                                                x-on:click="$wire.loadFilter(filter.settings), detail = null, showSavedFilters = false"
                                                            />
                                                            <x-icon
                                                                name="chevron-left"
                                                                class="w-4 h-4 cursor-pointer"
                                                                x-bind:class="{'-rotate-90': detail === index}"
                                                                x-on:click="detail === index ? detail = null : detail = index"
                                                            />
                                                        </div>
                                                    </div>
                                                    <div
                                                        x-transition
                                                        x-show="detail === index"
                                                    >
                                                        <div
                                                            class="flex flex-col space-y-4 justify-center items-center"
                                                            x-show="filter.settings.userFilters.length > 0"
                                                        >
                                                            <template x-for="(orFilters, orIndex) in filter.settings.userFilters">
                                                                <div class="flex flex-col justify-center items-center">
                                                                    <div class="flex justify-between">
                                                                        <div class="pt-1 flex gap-1">
                                                                            <template x-for="(filter, index) in orFilters">
                                                                                <div>
                                                                                    <x-badge flat primary>
                                                                                        <x-slot:label>
                                                                                            <span x-text="filterBadge(filter)"></span>
                                                                                        </x-slot:label>
                                                                                    </x-badge>
                                                                                    <template x-if="(orFilters.length - 1) !== index">
                                                                                        <x-badge flat negative :label="__('and')" />
                                                                                    </template>
                                                                                </div>
                                                                            </template>
                                                                        </div>
                                                                    </div>
                                                                    <template x-if="(filter.settings.userFilters.length - 1) !== orIndex">
                                                                        <div class="pt-3">
                                                                            <x-badge
                                                                                flat positive
                                                                                :label="__('or')"
                                                                            />
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <x-badge x-show="filter.settings.orderBy" flat amber>
                                                                <x-slot:label>
                                                                    <span>{{ __('Order by') }}</span>
                                                                    <span x-text="filter.settings.orderBy"></span>
                                                                    <span x-text="filter.settings.orderAsc ? '{{ __('asc') }}' : '{{ __('desc') }}'"></span>
                                                                </x-slot:label>
                                                            </x-badge>
                                                        </div>
                                                    </div>
                                                </x-card>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        @endif
                        <x-native-select
                            wire:target="loadFields"
                            wire:loading.attr="disabled"
                            x-model="newFilter.relation"
                        >
                            <option value="">{{  __('This table') }}</option>
                            <template x-for="relation in relations">
                                <option x-bind:value="relation.value" x-text="relation.label"></option>
                            </template>
                        </x-native-select>
                        <x-input
                            wire:target="loadFields"
                            wire:loading.attr="disabled"
                            x-ref="filterColumn"
                            required
                            x-model.lazy="newFilter.column"
                            placeholder="{{ __('Column') }}"
                            x-bind:list="$id('cols')"
                        />
                        <datalist x-bind:id="$id('cols')">
                            <template x-for="col in filterable">
                                <option x-bind:value="col" x-text="colLabels[col]"></option>
                            </template>
                        </datalist>
                        <div x-show="filterSelectType !== 'valueList' && filterSelectType !== 'search'">
                            <x-input
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
                                <option value="not like">{{ __('not like') }}</option>
                                <option value="is null">{{ __('is null') }}</option>
                                <option value="is not null">{{ __('is not null') }}</option>
                            </datalist>
                        </div>
                        <div x-show="filterSelectType === 'valueList'">
                            <x-native-select
                                x-model="newFilter.value"
                                placeholder="{{ __('Value') }}"
                            >
                                <option value=""></option>
                                <template x-for="item in filterValueLists[newFilter.column]">
                                    <option x-bind:value="item.value" x-text="item.label"></option>
                                </template>
                            </x-native-select>
                        </div>
                        <div x-show="filterSelectType === 'text'">
                            <x-input
                                x-bind:type="window.formatters.inputType(formatters[newFilter.column])"
                                x-model="newFilter.value"
                                placeholder="{{ __('Value') }}"
                                x-ref="filterValue"
                            />
                        </div>
                        <div x-show="filterSelectType === 'search'">
                            <x-select
                                x-bind:id="$id('filter-select-search')"
                                class="pb-4"
                                x-on:selected="newFilter.value = $event.detail.value"
                                option-value="id"
                                option-label="name"
                                option-description="description"
                                :clearable="false"
                                async-data=""
                            />
                        </div>
                        <div
                            x-cloak
                            x-show="newFilter.operator === 'like' || newFilter.operator === 'not like'"
                            class="text-xs text-slate-400 break-long-words max-w-md"
                        >
                            {{ __('When using the like or not like filter, you can use the % sign as a placeholder. Examples: "test%" for values that start with "test", "%test" for values that end with "test", and "%test%" for values that contain "test" anywhere.') }}
                        </div>
                        <x-button
                            wire:target="loadFields"
                            wire:loading.attr="disabled"
                            type="submit"
                            x-ref="filterAddButton"
                            primary
                        >
                            {{ __('Add filter') }}
                        </x-button>
                    </form>
                    <div class="flex flex-col space-y-4 justify-center items-center" x-cloak x-show="filters.length > 0 || orderByCol">
                        <template x-for="(orFilters, orIndex) in filters">
                            <div class="flex flex-col justify-center items-center">
                                <div
                                    x-on:click="filterIndex = orIndex"
                                    x-bind:class="filterIndex === orIndex ? 'ring-2 ring-indigo-600' : 'ring-1 ring-slate-700/10'"
                                    class="relative pointer-events-auto w-full rounded-lg bg-white p-4 pr-6.5
                                    text-sm leading-5 shadow-xl shadow-black/5 hover:bg-slate-50
                                    dark:bg-secondary-800"
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
                                                                <x-icon name="x" class="w-4 h-4" />
                                                            </button>
                                                        </x-slot>
                                                    </x-badge>
                                                    <template x-if="(orFilters.length - 1) !== index">
                                                        <x-badge
                                                            flat
                                                            negative
                                                            :label="__('and')"
                                                        />
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <template x-if="(filters.length - 1) !== orIndex">
                                    <div class="pt-3">
                                        <x-badge
                                            flat
                                            positive
                                            :label="__('or')"
                                        />
                                    </div>
                                </template>
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
                            x-show="filters.length > 0"
                            positive
                            :label="__('Add or')"
                            x-on:click="addOrFilter()"
                        />
                        @if(method_exists(auth()->user(), 'datatableUserSettings'))
                            <x-button
                                primary
                                class="w-full"
                                x-on:click="
                                    $wireui.confirmDialog({
                                        id: 'save-filter',
                                        icon: 'question',
                                        accept: {
                                            label: '{{ __('Save') }}',
                                            execute: () => {$wire.saveFilter(filterName, permanent); filterName = ''; permanent = false;},
                                        },
                                        reject: {
                                            label: '{{ __('Cancel') }}',
                                            execute: () => {
                                                filterName = ''
                                            }
                                        }
                                    })
                                "
                            >
                                {{ __('Save') }}
                            </x-button>
                        @endif
                    </div>
                </div>
            @endif
            @if($this->aggregatable)
                <div x-cloak x-show="tab === 'summarize'">
                    <div class="grid grid-cols-1 gap-3">
                        <template x-for="col in aggregatable">
                            <div>
                                <x-label>
                                <span x-text="colLabels[col]">
                                </span>
                                </x-label>
                                <x-checkbox
                                    :label="__('Sum')"
                                    x-bind:value="col"
                                    x-model="aggregatableCols.sum"
                                />
                                <x-checkbox
                                    :label="__('Average')"
                                    x-bind:value="col"
                                    x-model="aggregatableCols.avg"
                                />
                                <x-checkbox
                                    :label="__('Minimum')"
                                    x-bind:value="col"
                                    x-model="aggregatableCols.min"
                                />
                                <x-checkbox
                                    :label="__('Maximum')"
                                    x-bind:value="col"
                                    x-model="aggregatableCols.max"
                                />
                            </div>
                        </template>
                    </div>
                </div>
            @endif
            <div x-show="tab === 'columns'">
                <div x-bind:id="$id('table-cols')">
                    <template x-for="col in @js($this->availableCols)" :key="col">
                        <div x-bind:data-column="col">
                            <label x-bind:for="col" class="flex items-center">
                                <div class="relative flex items-start">
                                    <div class="flex items-center h-5">
                                        <x-checkbox
                                            x-bind:id="col"
                                            x-bind:value="col"
                                            x-model="cols"
                                        />
                                    </div>
                                    <div class="ml-2 text-sm">
                                        <label
                                            x-text="colLabels[col]"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-50"
                                            x-bind:for="col"
                                        >
                                        </label>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </template>
                </div>
            </div>
            @if($this->isExportable)
                <div x-show="tab === 'export'">
                    <template x-for="columnName in exportableColumns">
                        <x-checkbox x-bind:value="columnName" x-model="exportColumns">
                            <x-slot:label>
                                <span x-text="columnName"></span>
                            </x-slot:label>
                        </x-checkbox>
                    </template>
                    <div class="pt-3">
                        <x-button
                            spinner
                            x-on:click="$wire.export(exportColumns)"
                            primary
                            class="w-full"
                        >
                            {{ __('Export') }}
                        </x-button>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <x-slot:footer>
        <x-button x-on:click="showSidebar = false">{{ __('Close') }}</x-button>
    </x-slot:footer>
</x-tall-datatables::sidebar>
