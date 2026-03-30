export default function datatable_options(wire) {
    return {
        searchRelations: null,
        searchColumns: null,
        searchAggregatable: null,
        searchGroupable: null,
        dateCalculation: 0,
        filterName: '',
        permanent: false,
        withEnabledCols: true,
        sortCols: [],
        newFilter: {
            column: '',
            operator: '',
            value: [''],
            relation: '',
        },
        newFilterCalculation: {
            operator: '-',
            value: 1,
            unit: 'days',
            is_start_of: null,
            start_of: null,
        },
        filters: wire.userFilters || [],
        enabledCols: wire.enabledCols || [],
        filterValueLists: wire.filterValueLists || {},
        groupBy: wire.groupBy || null,
        orderByCol: wire.userOrderBy || '',
        orderAsc: wire.userOrderAsc ?? true,
        aggregatableCols: wire.aggregatableCols || {
            sum: [],
            avg: [],
            min: [],
            max: [],
        },
        exportColumns: [],
        relationTableFields: {},
        filterSelectType: 'text',
        filterIndex: 0,
        showSavedFilters: false,
        exportableColumns: [],
        selectedCols: [],
        selectedRelations: [],
        displayPath: [],
        aggregatable: [],
        groupable: [],
        operatorLabels: {},
        relationFormatters: {},
        _ready: false,
        _sidebarLoaded: false,

        searchable(items, search) {
            if (!items || !search) return items || [];
            if (Array.isArray(items)) {
                return items.filter((item) => {
                    const label =
                        typeof item === 'object'
                            ? item.label || item.col || ''
                            : this.getLabel(item);
                    return label
                        .toLowerCase()
                        .includes(search.toLowerCase());
                });
            }
            return Object.fromEntries(
                Object.entries(items).filter(([key, val]) => {
                    const label =
                        typeof val === 'object'
                            ? val.label || val.name || key
                            : this.getLabel(key);
                    return label
                        .toLowerCase()
                        .includes(search.toLowerCase());
                }),
            );
        },

        getLabel(col) {
            if (!col) return '';
            const labels = wire.colLabels || {};
            return (
                labels[col] ||
                col
                    .split('.')
                    .map(
                        (s) =>
                            s.charAt(0).toUpperCase() +
                            s.slice(1).replace(/_/g, ' '),
                    )
                    .join(' \u2192 ')
            );
        },

        getFilterInputType(col) {
            if (!col || col === '.') return 'text';
            const parts = col.split('.');
            const table =
                parts.length > 1 ? parts[0] || 'self' : 'self';
            const column = parts.length > 1 ? parts[1] : parts[0];
            const formatter =
                this.relationFormatters?.[table]?.[column] ?? null;
            if (!formatter) return 'text';
            if (
                formatter === 'date' ||
                formatter === 'datetime' ||
                formatter === 'immutable_date' ||
                formatter === 'immutable_datetime'
            )
                return 'date';
            if (
                formatter === 'integer' ||
                formatter === 'int' ||
                formatter === 'float' ||
                formatter === 'double' ||
                formatter === 'decimal'
            )
                return 'number';
            return 'text';
        },

        getCalculationLabel(calc) {
            if (!calc) return '';
            return (
                (calc.operator || '') +
                ' ' +
                (calc.value || '') +
                ' ' +
                (calc.unit || '')
            );
        },

        filterBadge(filter) {
            if (!filter) return '';
            const label =
                this.getLabel(filter.column) ?? filter.column;
            let value = filter.value;
            const listItem = (
                this.filterValueLists[filter.column] || []
            ).find((item) => item.value == value);
            if (listItem) value = listItem.label;
            if (Array.isArray(value)) {
                value = value
                    .map((item) => {
                        if (
                            item &&
                            typeof item === 'object' &&
                            item.hasOwnProperty('calculation')
                        ) {
                            return this.getCalculationLabel(
                                item.calculation,
                            );
                        }
                        return item;
                    })
                    .join(
                        ' ' +
                            (this.operatorLabels.and || '&') +
                            ' ',
                    );
            }
            return (
                label +
                ' ' +
                (this.operatorLabels[filter.operator] ||
                    filter.operator) +
                ' ' +
                value
            );
        },

        addFilter() {
            let newFilter = { ...this.newFilter };
            let filters = Array.isArray(this.filters)
                ? [...this.filters]
                : [];
            if (filters.length === 0) {
                filters.push([]);
                this.filterIndex = 0;
            }
            newFilter.operator = newFilter.operator || '=';
            if (
                newFilter.relation &&
                newFilter.relation !== '0'
            ) {
                newFilter.column =
                    newFilter.relation + '.' + newFilter.column;
                newFilter.relation = '';
            }
            filters[this.filterIndex] = [
                ...(filters[this.filterIndex] || []),
                newFilter,
            ];
            this.filters = filters;
            this.syncFilters();
            this.resetFilter();
            this.$nextTick(() =>
                this.$refs.filterColumn?.focus(),
            );
        },

        addOrFilter() {
            if (
                this.filters.length > 0 &&
                this.filters[this.filters.length - 1].length === 0
            ) {
                this.filterIndex = this.filters.length - 1;
                return;
            }
            this.filterIndex = this.filters.length;
            this.filters = [...this.filters, []];
        },

        removeFilter(index, groupIndex) {
            const filters = this.filters.map((group) => [
                ...group,
            ]);
            if (
                filters[groupIndex] &&
                index >= 0 &&
                index < filters[groupIndex].length
            ) {
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
                this.filters = this.filters.filter(
                    (_, i) => i !== index,
                );
                this.syncFilters();
            }
        },

        resetFilter() {
            this.filterSelectType = 'text';
            this.newFilter = {
                column: '',
                operator: '',
                value: [''],
                relation: '',
            };
        },

        syncFilters() {
            wire.userFilters = this.filters;
            wire.applyUserFilters();
        },

        addCalculation(index) {
            if (!this.newFilter.value[index]) {
                this.newFilter.value[index] = {};
            }
            this.newFilter.value[index] = {
                calculation: { ...this.newFilterCalculation },
            };
            this.newFilterCalculation = {
                operator: '-',
                value: 1,
                unit: 'days',
                is_start_of: null,
                start_of: null,
            };
        },

        columnSortHandle(item, position) {
            const oldIndex = this.enabledCols.indexOf(item);
            if (oldIndex === -1) return;
            const cols = [...this.enabledCols];
            const [movedItem] = cols.splice(oldIndex, 1);
            cols.splice(position, 0, movedItem);
            this.enabledCols = cols;
            wire.enabledCols = cols;
            wire.storeColLayout(cols);
        },

        resetLayout() {
            this._ready = false;
            wire.resetLayout().then(() => {
                this.enabledCols = wire.enabledCols || [];
                this._sidebarLoaded = false;
                this.loadSidebarData();
                this.$nextTick(() => {
                    this._ready = true;
                });
            });
        },

        loadFilterable() {
            this.filterValueLists = wire.filterValueLists || {};
        },

        getColumns() {
            wire.getExportableColumns().then((result) => {
                this.exportableColumns = result;
                this.exportColumns = this.enabledCols;
            });
        },

        async loadSidebarData() {
            if (this._sidebarLoaded) return;
            this._sidebarLoaded = true;

            const data = await wire.getSidebarData();
            const cols = data.selectedCols || [];
            this.relationTableFields['self'] = cols.map((c) =>
                typeof c === 'object' ? c.attribute || c.col : c,
            );

            // Store in both Alpine state and wire proxy
            this.selectedCols = cols;
            this.selectedRelations = data.selectedRelations || [];
            wire.selectedCols = cols;
            wire.selectedRelations = this.selectedRelations;
        },

        handleTabNavigate(tab) {
            if (
                tab === 'edit-filters' ||
                tab === 'columns' ||
                tab === 'export'
            ) {
                this.loadSidebarData();
            }

            if (tab === 'columns') {
                this.sortCols = this.enabledCols;
            }

            if (tab === 'export') {
                this.getColumns();
            }
        },

        async init() {
            this.enabledCols = wire.enabledCols || [];
            this.filters = Array.isArray(wire.userFilters)
                ? wire.userFilters
                : [];
            this.filterValueLists =
                wire.filterValueLists || {};
            this.groupBy = wire.groupBy || null;
            this.orderByCol = wire.userOrderBy || '';
            this.orderAsc = wire.userOrderAsc ?? true;
            this.aggregatableCols =
                wire.aggregatableCols || {
                    sum: [],
                    avg: [],
                    min: [],
                    max: [],
                };
            this.exportColumns = this.enabledCols;
            this.exportableColumns = this.enabledCols;
            this.selectedCols =
                wire.selectedCols?.length
                    ? wire.selectedCols
                    : this.selectedCols;
            this.selectedRelations =
                Object.keys(wire.selectedRelations || {})
                    .length
                    ? wire.selectedRelations
                    : this.selectedRelations;
            this.displayPath =
                wire.displayPath?.length
                    ? wire.displayPath
                    : this.displayPath;
            this._sidebarLoaded =
                this.selectedCols.length > 0;
            if (this._sidebarLoaded) {
                this.relationTableFields['self'] =
                    this.selectedCols.map((c) =>
                        typeof c === 'object'
                            ? c.attribute || c.col
                            : c,
                    );
            } else {
                this.loadSidebarData();
            }

            this.$watch('newFilter.column', () => {
                if (!this.newFilter.column) return;
                if (
                    this.filterValueLists.hasOwnProperty(
                        this.newFilter.column,
                    )
                ) {
                    this.filterSelectType = 'valueList';
                    this.newFilter.operator = '=';
                } else {
                    this.filterSelectType = 'text';
                }
            });

            this.$watch('newFilter.operator', () => {
                if (
                    this.newFilter.operator === 'is null' ||
                    this.newFilter.operator === 'is not null'
                ) {
                    this.filterSelectType = 'none';
                } else if (
                    this.filterValueLists.hasOwnProperty(
                        this.newFilter.column,
                    )
                ) {
                    this.filterSelectType = 'valueList';
                }
            });

            this.$watch(
                'newFilter.relation',
                async (value) => {
                    const key =
                        value === '' || value === '0'
                            ? 'self'
                            : value;
                    if (!this.relationTableFields[key]) {
                        const data = await wire.loadSlug(
                            value === '0' ? null : value,
                        );
                        const cols = data?.cols || [];
                        this.relationTableFields[key] =
                            cols.map((c) =>
                                typeof c === 'object'
                                    ? c.attribute || c.col
                                    : c,
                            );
                    }
                },
            );

            this.$watch('enabledCols', () => {
                if (!this._ready) return;
                wire.storeColLayout(this.enabledCols);
            });

            this.$watch('aggregatableCols', () => {
                if (!this._ready) return;
                wire.aggregatableCols = this.aggregatableCols;
                wire.applyAggregations();
            });

            this.$watch(
                () => wire.groupBy,
                (val) => (this.groupBy = val ?? null),
            );

            this.$watch(
                () => wire.userOrderBy,
                (val) => {
                    this.orderByCol = val || '';
                    this.orderAsc = wire.userOrderAsc ?? true;
                },
            );

            await this.$nextTick();
            this._ready = true;
        },
    };
}
