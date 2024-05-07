import {Sortable} from 'sortablejs';
import formatters from './formatters/formatters';

window.Sortable = Sortable;
window.formatters = formatters



document.addEventListener('alpine:init', () => {
    window.Alpine.data('data_table',
        ($wire) => ({
            async init() {
                await Promise.all([this.loadTableConfig(),this.loadFilterable()]);
                this.$nextTick(() => {
                    this.$watch('enabledCols', async () => {
                        await this.$wire.storeColLayout(this.enabledCols);
                        this.formatters = await this.$wire.getFormatters();
                        this.colLabels = await this.$wire.getColLabels(this.enabledCols);
                    });

                    const sortable = document.getElementById(this.$id('table-cols'));

                    if (sortable) new Sortable(sortable, {
                        animation: 150,
                        delay: 100,
                        onEnd: (e) => {
                            const name = e.item.dataset.column;
                            const oldIndex = this.enabledCols.indexOf(name);
                            const [movedItem] = this.enabledCols.splice(oldIndex, 1);
                            this.enabledCols.splice(e.newIndex, 0, movedItem);
                        }
                    });
                    this.$watch('search', async () => {
                        await this.$wire.startSearch();
                    });

                    this.$watch('aggregatableCols', async () => {
                        await this.$wire.applyAggregations();
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

                    this.$watch('newFilter.relation', async () => {
                        this.newFilter.column = '';
                        await this.loadRelationTableFields(this.newFilter.relation);
                    })

                    this.$watch('selected', () => {
                        this.$dispatch('tall-datatables-selected', this.selected);
                    })
                });

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
                                    this.$wire.eloquentEventOccurred(event, data);
                                });
                        });
                    });
                }
            },
            async loadTableConfig() {
                const result = await  $wire.getConfig();
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
            },
            data: $wire.entangle('data').live,
            showSidebar: false,
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
                    return item.value === filter.value
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

                this.showSidebar = true;
            },
            filterable: [],
            relationTableFields: {},
            relationFormatters: {},
            relationColLabels: {},
            async resetLayout() {
                await $wire.resetLayout();
                await this.loadTableConfig();
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
            async loadRelationTableFields(table = null) {
                let tableAlias = table;
                if (table === '') {
                    tableAlias = 'self';
                }

                if (this.relationTableFields.hasOwnProperty(tableAlias)) {
                    return;
                }

               const relationsTableCols =  await $wire.getRelationTableCols(table);
                this.relationTableFields[tableAlias] = Object.keys(relationsTableCols);
                this.relationFormatters[tableAlias] = relationsTableCols;

                if (! this.textFilter) {
                    this.textFilter = result.reduce((acc, curr) => {
                        acc[curr] = '';
                        return acc;
                    }, {});
                    this.$watch('textFilter', () => {
                        this.parseFilter();
                    });
                }

                const colLabels =  await $wire.getColLabels(this.relationTableFields[tableAlias]);
                Object.assign(this.relationColLabels, colLabels);

            },
            async loadFilterable(table = null) {
                const result =  await $wire.getFilterableColumns(table)
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
            async addFilter() {
                let newFilter = this.newFilter;
                if (this.filters.length === 0) {
                    this.filters.push([]);
                    this.filterIndex = 0;
                }

                newFilter.operator = Boolean(newFilter.operator) ? newFilter.operator : '=';
                if (newFilter.relation) {
                    newFilter.column = newFilter.relation + '.' + newFilter.column;
                    newFilter.relation = '';
                }

                this.filters[this.filterIndex].push(newFilter);
                this.resetFilter();
                this.filterSelectType = 'text';
                this.colLabels = await this.$wire.getColLabels();

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
            async clearFilters() {
                this.filters = [];
                this.filterIndex = 0;
                this.textFilter = {};
                await $wire.sortTable('');
            },
            resetFilter() {
                this.filterSelectType = 'text';
                this.newFilter = {column: '', operator: '', value: [], relation: ''};
            },
            filterName: '',
            permanent: false,
            exportColumns: [],
            exportableColumns: [],
            async getColumns() {
                this.exportableColumns = await $wire.getExportableColumns();
                this.exportColumns = this.enabledCols;
            },
            relations: [],
            savedFilters: [],
            async getSavedFilters() {
                this.savedFilters = await $wire.getSavedFilters();
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
                        return item.value === val
                    })?.label ?? val;
                }

                if (this.formatters.hasOwnProperty(col)) {
                    let type = this.formatters[col];
                    return formatters.setLabel(label).format({value: val, type: type, context: record});
                } else {
                    return formatters.setLabel(label).format({value: val, context: record});
                }
            },
        })
    )
});
