import {Sortable} from 'sortablejs';

window.Sortable = Sortable;
document.addEventListener('alpine:init', () => {
    window.Alpine.data('data_table',
        ($wire) => ({
            init() {
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
            columnsSortable(sortable) {
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

                this.showSidebar = true;
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
                if (newFilter.relation) {
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
                $wire.sortTable('');
            },
            resetFilter() {
                this.filterSelectType = 'text';
                this.newFilter = {column: '', operator: '', value: [], relation: ''};
            },
            filterName: '',
            permanent: false,
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
        })
    )
});

window.formatters = {
    label: null,
    setLabel(label) {
        this.label = label;

        return this;
    },
    format({value, type, options, context}) {
        if (value === null) {
            return value;
        }

        if (Array.isArray(value)) {
            return this.array(value);
        }

        if (typeof type === 'object') {
            options = type[1];
            type = type[0];
        }

        if (this[type]) {
            return this[type](value, options, context);
        }

        const guessedType = this.guessType(value);
        if (this[guessedType]) {
            return this[guessedType](value, options, context);
        }

        return value;
    },
    money(value, currency = null, context) {
        if (value === null) {
            return value;
        }

        const documentCurrencyCode = document.querySelector('meta[name="currency-code"]')?.getAttribute('content');

        let currencyCode;

        if (currency === null && documentCurrencyCode) {
            currencyCode = documentCurrencyCode;
        } else if (typeof currency === 'string') {
            currencyCode = currency;
        } else if (typeof currency === 'object' && currency?.hasOwnProperty('property')) {
            currencyCode = context[currency.property];
        } else if (
            typeof currency === 'object'
            && currency?.hasOwnProperty('currency')
            && currency?.currency?.hasOwnProperty('iso')
        ) {
            currencyCode = currency.currency.iso;
        } else if (typeof currency === 'object' && currency?.hasOwnProperty('iso')) {
            currencyCode = currency.iso;
        } else {
            currencyCode = documentCurrencyCode;
        }

        if (! (typeof currencyCode === 'string')) {
            return this.float(value);
        }

        try {
            return new Intl.NumberFormat(document.documentElement.lang, {
                style: 'currency',
                currency: currencyCode,
                minimumFractionDigits: 2,
            }).format(value);
        } catch (e) {
            return this.float(value) + ' ' + currencyCode;
        }
    },
    coloredMoney(value, currency = null, context) {
        const returnValue = this.money(value, currency, context);
        if (value < 0) {
            return `<span class="text-negative-500 dark:text-negative-700 font-semibold">${returnValue}</span>`;
        } else {
            return `<span class="text-positive-500 dark:text-positive-700 font-semibold">${returnValue}</span>`;
        }
    },
    percentage(value) {
        const percentageFormatter = new Intl.NumberFormat(document.documentElement.lang, {
            style: 'percent',
            minimumFractionDigits: 2,
        });

        return percentageFormatter.format(value);
    },
    bool(value) {
        if (value === 'false' || value === false || value === 0 || value === '0' || value === null) {
            return `<span class="bg-negative-500 dark:bg-negative-700 group inline-flex h-6 w-6 items-center justify-center rounded-full text-white outline-none">
                        <svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </span>`;
        } else {
            return `<span class="bg-positive-500 dark:bg-positive-700 group inline-flex h-6 w-6 items-center justify-center rounded-full text-white outline-none">
                    <svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </span>`;
        }
    },
    progressPercentage(value) {
        const formatter = new Intl.NumberFormat('en-US', {style: 'percent'});
        return `<div class="relative pt-1">
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                        <div style="width:${formatter.format(value)}" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary-500 dark:bg-primary-700"></div>
                    </div>
                    <span>${this.percentage(value)}</span>
                </div>`;
    },
    array(value) {
        if (! Array.isArray(value)) {
            return value;
        }

        return value.map(item => {
            return this.badge(item, 'indigo');
        })
            .join(' ');
    },
    object(value) {
        if (Object.keys(value).every(key => !isNaN(parseInt(key)) && isFinite(key))) {
            value = Array.from(value);

            return this.array(value);
        }

        return Object.keys(value).map(key => {
            const type = this.guessType(value[key]);
            const val = this.format({value: value[key], type: type});

            return this.badge(`${key}: ${val}`, 'indigo');
        }).join(' ');
    },
    boolean(value) {
        return this.bool(value);
    },
    date(value) {
        return new Date(value).toLocaleDateString(document.documentElement.lang, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        });
    },
    datetime(value) {
        return new Date(value).toLocaleString(document.documentElement.lang, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    },
    badge(value, colors) {
        if (! Boolean(value)) {
            return null;
        }

        const color = colors[value] || colors;
        if (this.label) {
            value = this.label;
        }

        return '<span class="outline-none inline-flex justify-center items-center group rounded gap-x-1 text-xs font-semibold px-2.5 py-0.5 text-' + color + '-600 bg-' + color + '-100 dark:text-' + color + '-400 dark:bg-slate-700">\n' +
            value + '\n' +
            '</span>';
    },
    relativeTime(value) {
        const current = new Date().getTime();
        const elapsed = current - value;
        const timeFormatter = new Intl.RelativeTimeFormat(document.documentElement.lang, {style: 'narrow'});
        const seconds = elapsed / 1000;
        const minutes = seconds / 60;
        const hours = minutes / 60;
        const days = hours / 24;
        const weeks = days / 7;
        const months = days / 30;
        const years = days / 365;

        switch (true) {
            case seconds < 60:
                return timeFormatter.format(Math.round(seconds) * -1, 'second');
            case minutes < 60:
                return timeFormatter.format(Math.round(minutes) * -1, 'minute');
            case hours < 24:
                return timeFormatter.format(Math.round(hours) * -1, 'hour');
            case days < 7:
                return timeFormatter.format(Math.round(days) * -1, 'day');
            case weeks < 4:
                return timeFormatter.format(Math.round(weeks) * -1, 'week');
            case months < 12:
                return timeFormatter.format(Math.round(months) * -1, 'month');
            default:
                return timeFormatter.format(Math.round(years) * -1, 'year');
        }
    },
    time(value) {
        // check if value is already in time format
        if (typeof value === 'string' && value.match(/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/)) {
            return value;
        }

        let seconds = Math.floor(value / 1000);
        let minutes = Math.floor(seconds / 60);
        seconds = seconds % 60;
        let hours = Math.floor(minutes / 60);
        minutes = minutes % 60;

        hours = hours.toString().padStart(2, '0');
        minutes = minutes.toString().padStart(2, '0');
        seconds = seconds.toString().padStart(2, '0');

        return `${hours}:${minutes}:${seconds}`;
    },
    float(value) {
        if (isNaN(parseFloat(value))) {
            return value;
        }
        let val = parseFloat(value);

        try {
            return val.toLocaleString(document.documentElement.lang);
        } catch (e) {
            return val;
        }
    },
    coloredFloat(value) {
        return this.coloredMoney(value, '');
    },
    int(value) {
        return parseInt(value);
    },
    string(value) {
        if (value === null) {
            return value;
        }

        if (this.label) {
            value = this.label;
        }

        return value.toString();
    },
    state(value, colors) {
        return this.badge(value, colors);
    },
    image(value) {
        if (! value) {
            return value;
        }

        return '<div class="dark:border-secondary-500 inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-200 dark:bg-gray-200">\n' +
            '    \n' +
            '            <img class="h-8 w-8 shrink-0 rounded-full object-contain object-center text-xl" src="' + value + '">\n' +
            '    \n' +
            '    </div>';
    },
    email(value) {
        return '<a href="mailto:' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    url(value) {
        return '<a href="' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    tel(value) {
        return '<a href="tel:' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    link(value) {
        return '<a href="' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    inputType(value) {
        switch (value) {
            case 'datetime':
                return 'datetime-local';
            case 'date':
                return 'date';
            case 'time':
                return 'time';
            case 'number':
            case 'int':
            case 'float':
            case 'money':
                return 'number';
            case 'email':
                return 'email';
            case 'password':
                return 'password';
            case 'tel':
                return 'tel';
            case 'url':
                return 'url';
            default:
                return 'text';
        }
    },
    guessType(value) {
        if (value === null) {
            return 'null';
        }

        if (Array.isArray(value)) {
            return 'array';
        }

        if (typeof value === 'object') {
            return 'object';
        }

        if (typeof value === 'boolean') {
            return 'boolean';
        }

        if (typeof value === 'string') {
            if (value.includes('://')) {
                return 'url';
            }

            if (value.includes('@')) {
                return 'email';
            }

            if (value.match(/^\d{4}-\d{2}-\d{2}(T|\s)\d{2}:\d{2}(:\d{2})?$/)) {
                return 'datetime';
            }

            if (value.match(/^\d{4}-\d{2}-\d{2}$/)) {
                return 'date';
            }

            if (value.match(/^\d{2}:\d{2}:\d{2}$/)) {
                return 'time';
            }

            if (value.match(/^\d+$/)) {
                return 'int';
            }

            if (value.match(/^\d+\.\d+$/)) {
                return 'float';
            }

            return 'string';
        }

        if (typeof value === 'number') {
            if (value % 1 === 0) {
                return 'int';
            }

            return 'float';
        }

        return 'string';
    }
}
