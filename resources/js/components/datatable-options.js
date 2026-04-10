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
        isShared: false,
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
        exportFormat: 'xlsx',
        exportFormatted: true,
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
        datePresetLabel: '',

        getFilters() {
            const uf = wire.userFilters;
            if (!uf || !Array.isArray(uf)) return [];
            return uf.filter((group) => Array.isArray(group));
        },

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
                if (filter.operator === 'between' && value.length === 2 &&
                    value[0]?.calculation && value[1]?.calculation) {
                    const presetLabel = this.getDatePresetLabel(value[0].calculation, value[1].calculation);
                    if (presetLabel) {
                        return label + ' = ' + presetLabel;
                    }
                }
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

        getDatePresetLabel(fromCalc, toCalc) {
            const presets = {
                'today': ['-', 0, 'days', 'day', '+', 0, 'days', 'day'],
                'yesterday': ['-', 1, 'days', 'day', '-', 1, 'days', 'day'],
                'this week': ['-', 0, 'weeks', 'week', '+', 0, 'weeks', 'week'],
                'this month': ['-', 0, 'months', 'month', '+', 0, 'months', 'month'],
                'this quarter': ['-', 0, 'months', 'quarter', '+', 0, 'months', 'quarter'],
                'this year': ['-', 0, 'years', 'year', '+', 0, 'years', 'year'],
                'last 7 days': ['-', 7, 'days', 'day', '+', 0, 'days', 'day'],
                'last 30 days': ['-', 30, 'days', 'day', '+', 0, 'days', 'day'],
                'last week': ['-', 1, 'weeks', 'week', '-', 1, 'weeks', 'week'],
                'last month': ['-', 1, 'months', 'month', '-', 1, 'months', 'month'],
                'last quarter': ['-', 3, 'months', 'quarter', '-', 0, 'months', 'quarter'],
                'last year': ['-', 1, 'years', 'year', '-', 1, 'years', 'year'],
            };

            for (const [label, def] of Object.entries(presets)) {
                const [fOp, fVal, fUnit, fSof, tOp, tVal, tUnit, tSof] = def;
                const fromMatch = fromCalc.operator === fOp &&
                    Number(fromCalc.value) === fVal && fromCalc.unit === fUnit && fromCalc.start_of === fSof;
                const toMatch = toCalc.operator === tOp &&
                    Number(toCalc.value) === tVal && toCalc.unit === tUnit && toCalc.start_of === tSof;
                if (fromMatch && toMatch) return label;
            }
            return null;
        },

        addFilter() {
            let newFilter = { ...this.newFilter };
            let filters = this.getFilters().map((g) => [...g]);
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
            wire.userFilters = filters;
            wire.applyUserFilters();
            this.resetFilter();
            this.$nextTick(() =>
                this.$refs.filterColumn?.focus(),
            );
        },

        addOrFilter() {
            const uf = wire.userFilters;
            this.filterIndex = Array.isArray(uf) ? uf.length : 0;
        },

        removeFilter(index, groupIndex) {
            wire.removeFilter(groupIndex, index);
        },

        removeFilterGroup(index) {
            wire.removeFilterGroup(index);
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

        applyDatePreset(key) {
            const presets = {
                today: {
                    label: 'today',
                    from: { operator: '-', value: 0, unit: 'days', is_start_of: '1', start_of: 'day' },
                    to: { operator: '+', value: 0, unit: 'days', is_start_of: '0', start_of: 'day' },
                },
                yesterday: {
                    label: 'yesterday',
                    from: { operator: '-', value: 1, unit: 'days', is_start_of: '1', start_of: 'day' },
                    to: { operator: '-', value: 1, unit: 'days', is_start_of: '0', start_of: 'day' },
                },
                this_week: {
                    label: 'this week',
                    from: { operator: '-', value: 0, unit: 'weeks', is_start_of: '1', start_of: 'week' },
                    to: { operator: '+', value: 0, unit: 'weeks', is_start_of: '0', start_of: 'week' },
                },
                this_month: {
                    label: 'this month',
                    from: { operator: '-', value: 0, unit: 'months', is_start_of: '1', start_of: 'month' },
                    to: { operator: '+', value: 0, unit: 'months', is_start_of: '0', start_of: 'month' },
                },
                this_quarter: {
                    label: 'this quarter',
                    from: { operator: '-', value: 0, unit: 'months', is_start_of: '1', start_of: 'quarter' },
                    to: { operator: '+', value: 0, unit: 'months', is_start_of: '0', start_of: 'quarter' },
                },
                this_year: {
                    label: 'this year',
                    from: { operator: '-', value: 0, unit: 'years', is_start_of: '1', start_of: 'year' },
                    to: { operator: '+', value: 0, unit: 'years', is_start_of: '0', start_of: 'year' },
                },
                last_7_days: {
                    label: 'last 7 days',
                    from: { operator: '-', value: 7, unit: 'days', is_start_of: '1', start_of: 'day' },
                    to: { operator: '+', value: 0, unit: 'days', is_start_of: '0', start_of: 'day' },
                },
                last_30_days: {
                    label: 'last 30 days',
                    from: { operator: '-', value: 30, unit: 'days', is_start_of: '1', start_of: 'day' },
                    to: { operator: '+', value: 0, unit: 'days', is_start_of: '0', start_of: 'day' },
                },
                last_week: {
                    label: 'last week',
                    from: { operator: '-', value: 1, unit: 'weeks', is_start_of: '1', start_of: 'week' },
                    to: { operator: '-', value: 1, unit: 'weeks', is_start_of: '0', start_of: 'week' },
                },
                last_month: {
                    label: 'last month',
                    from: { operator: '-', value: 1, unit: 'months', is_start_of: '1', start_of: 'month' },
                    to: { operator: '-', value: 1, unit: 'months', is_start_of: '0', start_of: 'month' },
                },
                last_quarter: {
                    label: 'last quarter',
                    from: { operator: '-', value: 3, unit: 'months', is_start_of: '1', start_of: 'quarter' },
                    to: { operator: '-', value: 0, unit: 'months', is_start_of: '1', start_of: 'quarter' },
                },
                last_year: {
                    label: 'last year',
                    from: { operator: '-', value: 1, unit: 'years', is_start_of: '1', start_of: 'year' },
                    to: { operator: '-', value: 1, unit: 'years', is_start_of: '0', start_of: 'year' },
                },
            };

            if (key === 'custom') {
                this.datePresetLabel = '';
                this.newFilter.operator = 'between';
                this.newFilter.value = [
                    { calculation: { ...this.newFilterCalculation } },
                    { calculation: { ...this.newFilterCalculation } },
                ];
                return;
            }

            const preset = presets[key];
            if (!preset) return;

            this.newFilter.operator = 'between';
            this.newFilter.value = [
                { calculation: { ...preset.from } },
                { calculation: { ...preset.to } },
            ];
            this.datePresetLabel = preset.label;
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
