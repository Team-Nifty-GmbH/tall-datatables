export default function data_table($wire)
{
    return {
        async init() {
            this.loadTableConfig();
            this.$nextTick(() => {
                this.$watch('enabledCols', () => {
                    $wire.storeColLayout(this.enabledCols);
                    $wire.getFormatters()
                        .then(
                            formatters => {
                                this.formatters = formatters;
                            }
                        );
                    $wire.getColLabels(this.enabledCols)
                        .then(
                            result => {
                                this.colLabels = result;
                            }
                        );
                });
            });
            this.loadFilterable()

            this.$watch('search', () => {
                $wire.startSearch();
            });

            this.$watch('aggregatableCols', () => {
                $wire.applyAggregations();
            });

            this.$watch('newFilter.column', () => {
                if (! Boolean(this.newFilter.column)) {
                    return;
                }

                let valueList = this.filterValueLists.hasOwnProperty(this.newFilter.column);

                if (valueList) {
                    this.filterSelectType = 'valueList';
                    this.newFilter.operator = '=';
                }
            })

            this.$watch('newFilter.operator', () => {
                if (this.newFilter.operator === 'is null' || this.newFilter.operator === 'is not null') {
                    this.filterSelectType = 'none';
                }
            })

            this.$watch('newFilter.relation', () => {
                this.newFilter.column = '';
                this.loadRelationTableFields(this.newFilter.relation);
            })

            this.$watch('selected', () => {
                this.$dispatch('tall-datatables-selected', this.selected);
            })

            if (window.Echo !== undefined) {
                this.$watch('broadcastChannels', (newChannels, oldChannels) => {
                    const removedChannels = Object.values(oldChannels).filter(channel => ! Object.values(newChannels).includes(channel));
                    const addedChannels = Object.values(newChannels).filter(channel => ! Object.values(oldChannels).includes(channel));

                    removedChannels.forEach(channel => {
                        Echo.leave(channel);
                    });

                    addedChannels.forEach(channel => {
                        Echo.private(channel)
                            .listenToAll((event, data) => {
                                $wire.eloquentEventOccurred(event, data);
                            });
                    });
                });
            }
        },
        columnSortHandle(item, position) {
            const oldIndex = this.enabledCols.indexOf(item);
            const [movedItem] = this.enabledCols.splice(oldIndex, 1);
            this.enabledCols.splice(position, 0, movedItem);
        },
        searchable(data, search = null) {
            if (!search) {
                return data;
            }

            // data could be an object or an array, search in both
            // if its an object we have to return an object
            if (typeof data === 'object') {
                let obj = {};
                for (const [key, value] of Object.entries(data)) {
                    if (JSON.stringify(value).toLowerCase().includes(search.toLowerCase())) {
                        obj[key] = value;
                    }
                }

                return obj;
            }

            // its an array, return all items that include the search string
            return data.filter(item => {
                return JSON.stringify(item).toLowerCase().includes(search.toLowerCase());
            });
        },
        loadTableConfig() {
            $wire.getConfig().then(
                result => {
                    this.enabledCols = result.enabledCols;
                    this.availableCols = result.availableCols;
                    this.sortable = result.sortable;
                    this.aggregatable = result.aggregatable;
                    this.selectable = result.selectable;
                    this.formatters = result.formatters;
                    this.leftAppend = result.leftAppend;
                    this.rightAppend = result.rightAppend;
                    this.topAppend = result.topAppend;
                    this.bottomAppend = result.bottomAppend;
                    this.searchRoute = result.searchRoute;
                    this.echoListeners = result.echoListeners;
                    this.operatorLabels = result.operatorLabels;
                    this.colLabels = result.colLabels;
                }
            )
        },
        data: $wire.entangle('data').live,
        enabledCols: [],
        availableCols: [],
        colLabels: [],
        operatorLabels: [],
        sortable: [],
        aggregatable: [],
        selectable: false,
        showSelectedActions: false,
        formatters: [],
        leftAppend: [],
        rightAppend: [],
        topAppend: [],
        bottomAppend: [],
        broadcastChannels: [],
        searchRoute: '',
        tab: 'edit-filters',
        showSavedFilters: false,
        filterValueLists: $wire.entangle('filterValueLists', true),
        filters: $wire.entangle('userFilters', true),
        aggregatableCols: $wire.entangle('aggregatableCols'),
        orderByCol: $wire.entangle('userOrderBy'),
        orderAsc: $wire.entangle('userOrderAsc'),
        stickyCols: $wire.entangle('stickyCols', true),
        initialized: $wire.entangle('initialized', true),
        search: $wire.entangle('search'),
        selected: $wire.entangle('selected'),
        filterBadge(filter) {
            if (! filter) {
                return;
            }

            const label = this.getLabel(filter.column) ?? filter.column;
            let value = this.filterValueLists[filter.column]?.find(item => {
                return item.value == filter.value
            })?.label ?? filter.value;

            if (Array.isArray(value)) {
                value = filter.value.map((item) => {
                    if (item.hasOwnProperty('calculation')) {
                        return this.getCalculationLabel(item.calculation);
                    }

                    return formatters.format({value: item});
                }).join(' ' + this.operatorLabels.and + ' ');
            } else {
                value = formatters.format({value: value});
            }


            return label + ' ' +
                (this.operatorLabels[filter.operator] || filter.operator) + ' ' +
                value;
        },
        getCalculationLabel(calculation) {
            if (! calculation) {
                return;
            }

            let label = this.getLabel('Now');

            if (calculation.value !== 0) {
                label = label + ' ' + calculation.operator + ' ' + calculation.value + ' ' + this.getLabel(calculation.unit);
            }

            if (calculation.is_start_of) {
                label = label + ' ' + this.getLabel('Start of') + ' ' + this.getLabel(calculation.start_of);
            }

            return label;
        },
        getData() {
            this.broadcastChannels = $wire.get('broadcastChannels') ?? [];

            if (this.data.hasOwnProperty('data')) {
                return this.data.data;
            }

            return this.data;
        },
        filterSelectType: 'text',
        loadSidebar(newFilter = null) {
            if (this.$refs.filterOperator) {
                if (newFilter) {
                    this.newFilter = newFilter;
                    this.tab = 'edit-filters';
                } else {
                    this.resetFilter();
                }

                this.getSavedFilters();

                if (Boolean(this.newFilter.column)) {
                    this.$nextTick(() => this.$refs.filterOperator?.focus());
                } else if(Boolean(this.newFilter.operator)) {
                    this.$nextTick(() => this.$refs.filterValue?.focus());
                } else {
                    this.$nextTick(() => this.$refs.filterColumn?.focus());
                }
                this.showSavedFilters = false;
            }


            $slideOpen('data-table-sidebar-' + $wire.id.toLowerCase());
        },
        filterable: [],
        relationTableFields: {},
        relationFormatters: {},
        relationColLabels: {},
        resetLayout() {
            $wire.resetLayout().then(
                () => {
                    this.loadTableConfig();
                }
            )
        },
        getLabel(col) {
            return this.colLabels[col] || col.label || this.relationColLabels[col] || this.operatorLabels[col] || col;
        },
        getFilterInputType(col) {
            if (! col || col === '.') {
                return 'text';
            }

            let splittedCol = col.split('.');
            let table = 'self';

            if (splittedCol.length > 1) {
                table = splittedCol[0] || 'self';
                col = splittedCol[1];
            }

            const formatter = this.relationFormatters?.[table]?.[col] ?? null;

            return formatters.inputType(formatter)
        },
        loadRelationTableFields(table = null) {
            let tableAlias = table;
            if (table === '') {
                tableAlias = 'self';
            }

            if (this.relationTableFields.hasOwnProperty(tableAlias)) {
                return;
            }

            $wire.getRelationTableCols(table).then(
                result => {
                    this.relationTableFields[tableAlias] = Object.keys(result);
                    this.relationFormatters[tableAlias] = result;
                    $wire.getColLabels(this.relationTableFields[tableAlias])
                        .then(
                            result => {
                                Object.assign(this.relationColLabels, result);
                            }
                        );

                    if (! this.textFilter) {
                        this.textFilter = result.reduce((acc, curr) => {
                            acc[curr] = '';
                            return acc;
                        }, {});
                        this.$watch('textFilter', () => {
                            this.parseFilter();
                        });
                    }
                }
            );
        },
        loadFilterable(table = null) {
            $wire.getFilterableColumns(table)
                .then(
                    result => {
                        this.filterable = result;

                        if (! this.textFilter) {
                            this.textFilter = result.reduce((acc, curr) => {
                                acc[curr] = '';
                                return acc;
                            }, {});
                            this.$watch('textFilter', () => {
                                this.parseFilter();
                            });
                        }
                    }
                );
        },
        filterIndex: 0,
        textFilter: null,
        newFilter: {column: '', operator: '', value: [], relation: ''},
        newFilterCalculation: {value: 0, operator: '-', unit: 'days', is_start_of: "", start_of: 'day'},
        addCalculation(index) {
            // check if the index exists, otherwise add it
            if (! this.newFilter.value[index]) {
                this.newFilter.value[index] = {};
            }

            this.newFilter.value[index].calculation = this.newFilterCalculation;
            this.newFilterCalculation = {value: 0, operator: '-', unit: 'days', is_start_of: "", start_of: 'day'};
        },
        parseFilter() {
            let filters = [];
            for (const [key, value] of Object.entries(this.textFilter)) {
                if (value === '') {
                    continue;
                }

                let filterValue = value;

                let operator = null;
                if (this.filterValueLists.hasOwnProperty(key)) {
                    operator = '='
                } else {
                    operator = value.match(/^(>=|<=|!=|=|like|not like|>|<|is null|is not null)/i);
                }

                if (! operator
                    && (this.formatters[key] === 'date' || this.formatters[key] === 'datetime')
                    && filterValue.length > 6
                    && (new Date(filterValue) !== 'Invalid Date' && ! isNaN(new Date(filterValue)))
                ) {
                    operator = '=';
                }

                if (operator) {
                    filters.push({
                        column: key,
                        operator: operator[0].toLowerCase(),
                        value: value.replace(operator[0], '').trim(),
                        relation: '',
                        textFilterKey: true,
                    });

                    continue;
                }

                // check if value starts or ends with %, if so use like and dont add % to value
                if (! value.trim().startsWith('%') && ! value.trim().endsWith('%')) {
                    filterValue = '%' + value.trim() + '%';
                }

                filters.push({
                    column: key,
                    operator: 'like',
                    value: filterValue,
                    relation: '',
                    textFilterKey: true,
                });
            }

            this.filters = filters.length ? [filters] : [];
        },
        addFilter() {
            let newFilter = this.newFilter;
            if (this.filters.length === 0) {
                this.filters.push([]);
                this.filterIndex = 0;
            }

            newFilter.operator = Boolean(newFilter.operator) ? newFilter.operator : '=';
            if (newFilter.relation && newFilter.relation !== '0') {
                newFilter.column = newFilter.relation + '.' + newFilter.column;
                newFilter.relation = '';
            }

            this.filters[this.filterIndex].push(newFilter);
            this.resetFilter();
            this.filterSelectType = 'text';
            $wire.getColLabels().then(result => {this.colLabels = result;});

            this.$nextTick(() => this.$refs.filterColumn.focus());
        },
        addOrFilter() {
            if (this.filters[this.filters.length - 1].length === 0) {
                this.filterIndex = this.filters.length - 1;
                return;
            }

            this.filterIndex = this.filters.length;
            this.filters.push([]);
        },
        removeFilter(index, groupIndex) {
            const innerArray = this.filters[groupIndex];
            if (innerArray) {
                if (index >= 0 && index < innerArray.length) {
                    let removed = innerArray.splice(index, 1);

                    if (removed[0].textFilterKey) {
                        this.textFilter[removed[0].column] = '';
                    }

                    if (innerArray.length === 0) {
                        this.removeFilterGroup(groupIndex)
                    }
                }
            }
        },
        removeFilterGroup(index) {
            if (index >= 0 && index < this.filters.length) {
                this.filters.splice(index, 1);
            }
        },
        clearFilters() {
            this.filters = [];
            this.filterIndex = 0;
            this.textFilter = {};
            $wire.forgetSessionFilter();
            $wire.sortTable('');
        },
        resetFilter() {
            this.filterSelectType = 'text';
            this.newFilter = {column: '', operator: '', value: [], relation: ''};
        },
        filterName: '',
        permanent: false,
        withEnabledCols: true,
        exportColumns: [],
        exportableColumns: [],
        getColumns() {
            $wire.getExportableColumns().then(result => {
                this.exportableColumns = result;
                this.exportColumns = this.enabledCols;
            })
        },
        relations: [],
        savedFilters: [],
        getSavedFilters() {
            $wire.getSavedFilters().then(result => {this.savedFilters = result})
        },
        loadSavedFilter() {
            $wire.loadSavedFilter().then(result => {
                this.loadTableConfig();
            });
        },
        toggleStickyCol(col) {
            if (this.stickyCols.includes(col)) {
                this.stickyCols.splice(this.stickyCols.indexOf(col), 1);
            } else {
                this.stickyCols.push(col);
            }
        },
        formatter(col, record) {
            const val = record[col] ?? null;
            let label;

            if (this.filterValueLists.hasOwnProperty(col)) {
                label = this.filterValueLists[col].find(item => {
                    return item.value == val
                })?.label ?? val;
            }

            if (this.formatters.hasOwnProperty(col)) {
                let type = this.formatters[col];
                return formatters.setLabel(label).format({value: val, type: type, context: record});
            } else {
                return formatters.setLabel(label).format({value: val, context: record});
            }
        },
    }
}
