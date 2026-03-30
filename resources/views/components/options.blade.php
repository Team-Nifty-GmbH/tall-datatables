<div
    class="mt-2"
    x-data="{
        searchRelations: null,
        searchColumns: null,
        searchAggregatable: null,
        searchGroupable: null,
        dateCalculation: 0,
        filterName: '',
        permanent: false,
        withEnabledCols: true,
        tab: 'edit-filters',
        sortCols: [],
        newFilter: {column: '', operator: '=', value: [''], relation: ''},
        newFilterCalculation: {operator: '-', value: 1, unit: 'days', is_start_of: null, start_of: null},
        filters: $wire.userFilters || [],
        enabledCols: $wire.enabledCols || [],
        filterValueLists: $wire.filterValueLists || {},
        groupBy: $wire.groupBy || null,
        orderByCol: $wire.userOrderBy || '',
        orderAsc: $wire.userOrderAsc ?? true,
        aggregatable: {{ Js::from($this->getAggregatable()) }},
        aggregatableCols: $wire.aggregatableCols || {sum: [], avg: [], min: [], max: []},
        groupable: {{ Js::from($this->getGroupableCols()) }},
        exportColumns: [],
        relationTableFields: {},
        filterSelectType: 'text',
        filterIndex: 0,
        showSavedFilters: false,
        exportableColumns: [],
        operatorLabels: {{ Js::from($this->getOperatorLabels()) }},
        searchable(items, search) {
            if (!items || !search) return items || [];
            if (Array.isArray(items)) {
                return items.filter(item => {
                    const label = typeof item === 'object' ? (item.label || item.col || '') : item;
                    return label.toLowerCase().includes(search.toLowerCase());
                });
            }
            return Object.fromEntries(
                Object.entries(items).filter(([key, val]) => {
                    const label = typeof val === 'object' ? (val.label || val.name || key) : key;
                    return label.toLowerCase().includes(search.toLowerCase());
                })
            );
        },
        getLabel(col) {
            if (!col) return '';
            const labels = $wire.colLabels || {};
            return labels[col] || col.split('.').map(s => s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g, ' ')).join(' \u2192 ');
        },
        getFilterInputType(col) {
            if (!col || col === '.') return 'text';
            const parts = col.split('.');
            const table = parts.length > 1 ? (parts[0] || 'self') : 'self';
            const column = parts.length > 1 ? parts[1] : parts[0];
            const formatter = this.relationFormatters?.[table]?.[column] ?? null;
            if (!formatter) return 'text';
            if (formatter === 'date' || formatter === 'datetime' || formatter === 'immutable_date' || formatter === 'immutable_datetime') return 'date';
            if (formatter === 'integer' || formatter === 'int' || formatter === 'float' || formatter === 'double' || formatter === 'decimal') return 'number';
            return 'text';
        },
        getCalculationLabel(calc) {
            if (!calc) return '';
            return (calc.operator || '') + ' ' + (calc.value || '') + ' ' + (calc.unit || '');
        },
        filterBadge(filter) {
            if (!filter) return '';
            const label = this.getLabel(filter.column) ?? filter.column;
            let value = filter.value;
            const listItem = (this.filterValueLists[filter.column] || []).find(item => item.value == value);
            if (listItem) value = listItem.label;
            if (Array.isArray(value)) {
                value = value.map(item => {
                    if (item && typeof item === 'object' && item.hasOwnProperty('calculation')) {
                        return this.getCalculationLabel(item.calculation);
                    }
                    return item;
                }).join(' ' + (this.operatorLabels.and || '&') + ' ');
            }
            return label + ' ' + (this.operatorLabels[filter.operator] || filter.operator) + ' ' + value;
        },
        addFilter() {
            let newFilter = {...this.newFilter};
            let filters = Array.isArray(this.filters) ? [...this.filters] : [];
            if (filters.length === 0) {
                filters.push([]);
                this.filterIndex = 0;
            }
            newFilter.operator = newFilter.operator || '=';
            if (newFilter.relation && newFilter.relation !== '0') {
                newFilter.column = newFilter.relation + '.' + newFilter.column;
                newFilter.relation = '';
            }
            filters[this.filterIndex] = [...(filters[this.filterIndex] || []), newFilter];
            this.filters = filters;
            this.syncFilters();
            this.resetFilter();
            this.$nextTick(() => this.$refs.filterColumn?.focus());
        },
        addOrFilter() {
            if (this.filters.length > 0 && this.filters[this.filters.length - 1].length === 0) {
                this.filterIndex = this.filters.length - 1;
                return;
            }
            this.filterIndex = this.filters.length;
            this.filters = [...this.filters, []];
        },
        removeFilter(index, groupIndex) {
            const filters = this.filters.map(group => [...group]);
            if (filters[groupIndex] && index >= 0 && index < filters[groupIndex].length) {
                filters[groupIndex].splice(index, 1);
                if (filters[groupIndex].length === 0) {
                    filters.splice(groupIndex, 1);
                }
                this.filters = filters;
                this.syncFilters();
            }
        },
        removeFilterGroup(index) {
            if (index >= 0 && index < this.filters.length) {
                this.filters = this.filters.filter((_, i) => i !== index);
                this.syncFilters();
            }
        },
        resetFilter() {
            this.filterSelectType = 'text';
            this.newFilter = {column: '', operator: '=', value: [''], relation: ''};
        },
        syncFilters() {
            $wire.userFilters = this.filters;
            $wire.applyUserFilters();
        },
        addCalculation(index) {
            if (!this.newFilter.value[index]) {
                this.newFilter.value[index] = {};
            }
            this.newFilter.value[index] = {calculation: {...this.newFilterCalculation}};
            this.newFilterCalculation = {operator: '-', value: 1, unit: 'days', is_start_of: null, start_of: null};
        },
        columnSortHandle(item, position) {
            const oldIndex = this.enabledCols.indexOf(item);
            if (oldIndex === -1) return;
            const cols = [...this.enabledCols];
            const [movedItem] = cols.splice(oldIndex, 1);
            cols.splice(position, 0, movedItem);
            this.enabledCols = cols;
            $wire.enabledCols = cols;
            $wire.storeColLayout(cols);
        },
        resetLayout() {
            this._ready = false;
            $wire.resetLayout().then(() => {
                this.enabledCols = $wire.enabledCols || [];
                this.$nextTick(() => { this._ready = true; });
            });
        },
        loadFilterable() {
            // v2: filter columns are loaded from parent's filterValueLists
            this.filterValueLists = $wire.filterValueLists || {};
        },
        getColumns() {
            $wire.getExportableColumns().then(result => {
                this.exportableColumns = result;
                this.exportColumns = this.enabledCols;
            });
        },
        relationFormatters: {},
        _ready: false,
        async init() {
            this.enabledCols = $wire.enabledCols || [];
            this.filters = Array.isArray($wire.userFilters) ? $wire.userFilters : [];
            this.filterValueLists = $wire.filterValueLists || {};
            this.groupBy = $wire.groupBy || null;
            this.orderByCol = $wire.userOrderBy || '';
            this.orderAsc = $wire.userOrderAsc ?? true;
            this.aggregatableCols = $wire.aggregatableCols || {sum: [], avg: [], min: [], max: []};
            this.exportColumns = this.enabledCols;
            this.exportableColumns = this.enabledCols;

            // Populate relationTableFields from selectedCols for the filter column datalist
            const cols = $wire.selectedCols || [];
            this.relationTableFields['self'] = cols.map(c => typeof c === 'object' ? c.attribute || c.col : c);


            this.$watch('newFilter.column', () => {
                if (!this.newFilter.column) return;
                if (this.filterValueLists.hasOwnProperty(this.newFilter.column)) {
                    this.filterSelectType = 'valueList';
                    this.newFilter.operator = '=';
                } else {
                    this.filterSelectType = 'text';
                }
            });

            this.$watch('newFilter.operator', () => {
                if (this.newFilter.operator === 'is null' || this.newFilter.operator === 'is not null') {
                    this.filterSelectType = 'none';
                } else if (this.filterValueLists.hasOwnProperty(this.newFilter.column)) {
                    this.filterSelectType = 'valueList';
                }
            });

            this.$watch('newFilter.relation', async (value) => {
                const key = value === '' || value === '0' ? 'self' : value;
                if (!this.relationTableFields[key]) {
                    await $wire.loadSlug(value === '0' ? null : value);
                    const cols = $wire.selectedCols || [];
                    this.relationTableFields[key] = cols.map(c => typeof c === 'object' ? c.attribute || c.col : c);
                }
            });

            this.$watch('enabledCols', () => {
                if (!this._ready) return;
                $wire.storeColLayout(this.enabledCols);
            });

            this.$watch('aggregatableCols', () => {
                if (!this._ready) return;
                $wire.aggregatableCols = this.aggregatableCols;
                $wire.applyAggregations();
            });

            await this.$nextTick();
            this._ready = true;
        },
    }"
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
                    x-on:click="$tsui.close.modal('date-calculation')"
                />
                <x-button
                    :text="__('Save')"
                    x-on:click="addCalculation(dateCalculation); $tsui.close.modal('date-calculation');"
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
                            'border-indigo-500! text-indigo-600': tab === 'edit-filters',
                        }"
                        class="cursor-pointer border-b-2 border-transparent px-1 py-4 text-sm font-medium whitespace-nowrap text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-50"
                    >
                        {{ __('Filters') }}
                    </button>
                @endif

                <button
                    wire:loading.attr="disabled"
                    x-on:click.prevent="sortCols = enabledCols; tab = 'columns';"
                    x-bind:class="{ 'border-indigo-500! text-indigo-600': tab === 'columns' }"
                    class="cursor-pointer border-b-2 border-transparent px-1 py-4 text-sm font-medium whitespace-nowrap text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-50"
                >
                    {{ __('Columns') }}
                </button>

                @if ($this->aggregatable)
                    <button
                        wire:loading.attr="disabled"
                        x-on:click.prevent="sortCols = enabledCols; tab = 'summarize';"
                        x-bind:class="{ 'border-indigo-500! text-indigo-600': tab === 'summarize' }"
                        class="cursor-pointer border-b-2 border-transparent px-1 py-4 text-sm font-medium whitespace-nowrap text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-50"
                    >
                        {{ __('Summarize') }}
                    </button>
                @endif

                <button
                    wire:loading.attr="disabled"
                    x-on:click.prevent="tab = 'grouping';"
                    x-bind:class="{ 'border-indigo-500! text-indigo-600': tab === 'grouping' }"
                    class="cursor-pointer border-b-2 border-transparent px-1 py-4 text-sm font-medium whitespace-nowrap text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-50"
                >
                    {{ __('Group') }}
                </button>
                @if ($this->isExportable)
                    <button
                        wire:loading.attr="disabled"
                        x-on:click.prevent="
                            getColumns()
                            tab = 'export'
                        "
                        x-bind:class="{ 'border-indigo-500! text-indigo-600': tab === 'export' }"
                        class="cursor-pointer border-b-2 border-transparent px-1 py-4 text-sm font-medium whitespace-nowrap text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-50"
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
                            x-if="$wire.savedFilters?.length > 0"
                        >
                            <div>
                                <div
                                    class="dark:bg-secondary-800 dark:border-secondary-600 dark:text-secondary-400 border-secondary-300 focus:ring-primary-500 focus:border-primary-500 block flex w-full cursor-pointer justify-between rounded-md border bg-white px-3 py-2 text-base shadow-sm focus:ring-1 focus:outline-none sm:text-sm"
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
                            x-for="relation in $wire.selectedRelations"
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
                                    x-on:click="dateCalculation = 0; $tsui.open.modal('date-calculation');"
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
                                        x-on:click="dateCalculation = 1; $tsui.open.modal('date-calculation');"
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
                        x-model="$wire.withSoftDeletes"
                        x-on:change="$wire.startSearch()"
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
                    x-show="filters.length > 0 || orderByCol || groupBy"
                >
                    <template x-for="(orFilters, orIndex) in filters">
                        <div class="flex flex-col items-center justify-center">
                            <div
                                x-on:click="filterIndex = orIndex"
                                x-bind:class="filterIndex === orIndex ? 'ring-2 ring-indigo-600' : 'ring-1 ring-slate-700/10'"
                                class="dark:bg-secondary-800 pointer-events-auto relative w-full rounded-lg bg-white p-4 pr-6.5 text-sm leading-5 shadow-xl shadow-black/5 hover:bg-slate-50"
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
                                <button
                                    type="button"
                                    x-on:click="$wire.sortTable('')"
                                >
                                    <x-icon name="x-mark" class="h-4 w-4" />
                                </button>
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
                                <button
                                    type="button"
                                    x-on:click="$wire.setGroupBy(null)"
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
                            x-on:click="$tsui.open.modal('save-filter')"
                        >
                            {{ __('Save') }}
                        </x-button>
                    @endif
                </div>
            </div>
        @endif

        <div x-cloak x-show="tab === 'columns'">
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
                            class="min-w-0"
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
                                                    class="block truncate"
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
                                x-on:click="searchRelations = null; searchColumns = null; $wire.loadSlug()"
                            >
                                <span class="whitespace-nowrap">
                                    {{ __('This table') }}
                                </span>
                            </x-button>
                            <x-icon name="chevron-right" class="h-4 w-4" />
                        </div>
                        <template
                            x-for="segment in $wire.displayPath"
                        >
                            <div class="flex items-center gap-1.5">
                                <x-button
                                    flat
                                    color="indigo"
                                    x-on:click="searchRelations = null; searchColumns = null; $wire.loadSlug(segment.value)"
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
                    <div class="grid grid-cols-2 gap-1.5 overflow-hidden">
                        <div class="min-w-0 overflow-hidden">
                            <div class="pb-2">
                                <x-input
                                    type="search"
                                    x-model.debounce.300ms="searchColumns"
                                    placeholder="{{ __('Search') }}"
                                    class="w-full"
                                />
                            </div>
                            <template
                                x-for="col in searchable($wire.selectedCols, searchColumns)"
                            >
                                <label class="flex min-w-0 cursor-pointer items-center gap-1.5 overflow-hidden">
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
                            <div class="pb-2">
                                <x-input
                                    type="search"
                                    x-model.debounce.300ms="searchRelations"
                                    placeholder="{{ __('Search') }}"
                                    class="w-full"
                                />
                            </div>
                            <template
                                x-for="relation in searchable($wire.selectedRelations, searchRelations)"
                            >
                                <div
                                    class="flex min-w-0 cursor-pointer items-center gap-1.5 overflow-hidden"
                                    x-on:click="
                                        searchRelations = null
                                        searchColumns = null
                                        $wire.loadRelation(relation.model, relation.name)
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
        </div>

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

        <div x-cloak x-show="tab === 'grouping'" x-data="{ searchGroupable: null }">
            <div class="mb-3 pb-3 border-b border-gray-200 dark:border-secondary-700" x-show="groupBy" x-cloak>
                <x-label class="mb-2">{{ __('Rows per group') }}</x-label>
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
                    class="flex items-center justify-between rounded-lg border p-3"
                    x-bind:class="! groupBy ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-secondary-700'"
                >
                    <x-radio
                        :label="__('No grouping')"
                        value=""
                        x-bind:checked="! groupBy"
                        x-on:change="$wire.setGroupBy(null)"
                    />
                    <x-icon
                        name="view-columns"
                        class="h-5 w-5 text-gray-400"
                        x-bind:class="! groupBy && 'text-primary-500'"
                    />
                </div>
                <template x-for="col in searchable(groupable, searchGroupable)">
                    <x-radio
                        x-bind:value="col"
                        x-bind:checked="groupBy === col"
                        x-on:change="$wire.setGroupBy(col)"
                    >
                        <x-slot:label>
                            <span x-text="getLabel(col)"></span>
                        </x-slot:label>
                    </x-radio>
                </template>
            </div>
        </div>

        @if ($this->isExportable)
            <div x-cloak x-show="tab === 'export'">
                @foreach ($this->enabledCols as $col)
                    <div>
                        <label class="flex items-center">
                            <div class="relative flex items-start">
                                <div class="flex h-5 items-center">
                                    <input
                                        type="checkbox"
                                        class="border-secondary-300 text-primary-600 focus:ring-primary-600 focus:border-primary-400 dark:border-secondary-500 dark:checked:border-secondary-600 dark:focus:ring-secondary-600 dark:focus:border-secondary-500 dark:bg-secondary-600 dark:text-secondary-600 dark:focus:ring-offset-secondary-800 form-checkbox rounded transition duration-100 ease-in-out"
                                        value="{{ $col }}"
                                        x-model="exportColumns"
                                    />
                                </div>
                                <div class="ml-2 text-sm">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        {{ $this->colLabels[$col] ?? \Illuminate\Support\Str::headline($col) }}
                                    </label>
                                </div>
                            </div>
                        </label>
                    </div>
                @endforeach
                <div class="pt-3">
                    <x-button
                        loading
                        x-on:click="$wire.export(exportColumns); $tsui.close.slide('data-table-sidebar-' + $wire.id.toLowerCase());"
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
