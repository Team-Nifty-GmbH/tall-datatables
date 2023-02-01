import {Sortable} from 'sortablejs';
import _ from 'lodash';
window._ = _;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('data_table',
    ($wire) => ({
        init() {
            window.Sortable = Sortable;
            {
                this.$wire.getConfig().then(
                    result => {
                        this.cols = result.cols;
                        this.enabledCols = result.enabledCols;
                        this.colLabels = result.colLabels;
                        this.sortable = result.sortable;
                        this.selectable = result.selectable;
                        this.stretchCol = result.stretchCol;
                        this.formatters = result.formatters;
                        this.searchRoute = result.searchRoute;

                        this.$watch('cols', () => {
                            this.$wire.storeColLayout(this.cols);
                        });
                    }
                ),
                new Sortable(document.querySelector('#table-cols'), {
                    animation: 150,
                    delay: 100,
                    onEnd: (e) => {
                        const el = this.enabledCols[e.oldIndex];
                        let oldCols = Object.values(this.enabledCols);
                        // move element from e.oldIndex to e.newIndex
                        oldCols.splice(e.oldIndex, 1);
                        oldCols.splice(e.newIndex, 0, el);

                        this.enabledCols = oldCols;
                        this.cols = this.enabledCols.filter(value => this.cols.includes(value));
                    }
                }),
                this.loadFilterable(),
                this.$watch('newFilter.column', () => {
                    if (! Boolean(this.newFilter.column)) {
                        return;
                    }

                    let valueList = this.filterValueLists.hasOwnProperty(this.newFilter.column);

                    if (! valueList) {
                        $wire.resolveForeignKey(this.newFilter.column, this.newFilter.relation).then(
                            result => {
                                if (result === null) {
                                    this.filterSelectType = 'text';
                                    return;
                                }

                                if (typeof result === 'string') {
                                    this.filterSelectType = 'search';
                                    this.newFilter.operator = '=';
                                    Alpine.$data(document.querySelector('#filter-select-search')).asyncData.api = this.searchRoute + '/' + result;
                                } else if (typeof result === 'array' || typeof result === 'object') {
                                    this.filterSelectType = 'valueList';
                                    this.newFilter.operator = '=';
                                    this.filterValueLists[this.newFilter.column] = result;
                                    valueList = true;
                                }
                            });
                    }

                    if (valueList) {
                        this.filterSelectType = 'valueList';
                        this.newFilter.operator = '=';
                    }
                }),
                this.$watch('newFilter.relation', () => {
                    this.loadFilterable(this.newFilter.relation);
                })
            }
        },
        data: $wire.entangle('data'),
        showSidebar: false,
        cols: [],
        enabledCols: [],
        colLabels: [],
        sortable: [],
        selectable: [],
        stretchCol: [],
        formatters: [],
        searchRoute: '',
        intendentedCols: [],
        tab: 'edit-filters',
        showSavedFilters: false,
        filterValueLists: $wire.entangle('filterValueLists'),
        filters: $wire.entangle('userFilters'),
        orderByCol: $wire.entangle('orderBy'),
        orderAsc: $wire.entangle('orderAsc'),
        initialized: $wire.entangle('initialized'),
        search: $wire.entangle('search'),
        selected: $wire.entangle('selected').defer,
        filterBadge(filter) {
            const label = this.colLabels[filter.column] ?? filter.column;
            const value = filter.value;
            return label + ' ' + filter.operator + ' ' + value;
        },
        getData() {
            if (this.data.hasOwnProperty('data')) {
                return this.data.data;
            }

            return this.data;
        },
        filterSelectType: 'text',
        loadSidebar(newFilter = null) {
            if (newFilter) {
                this.newFilter = newFilter;
            } else {
                this.resetFilter();
            }

            this.loadRelations(this.newFilter.relation);

            this.getSavedFilters();

            if (Boolean(this.newFilter.column)) {
                this.$nextTick(() => this.$refs.filterOperator.focus());
            } else if(Boolean(this.newFilter.operator)) {
                this.$nextTick(() => this.$refs.filterValue.focus());
            } else {
                this.$nextTick(() => this.$refs.filterColumn.focus());
            }

            this.showSidebar = true;
            this.showSavedFilters = false;
        },
        filterable: [],
        loadFilterable(table = null) {
            $wire.loadFields(table)
                .then(
                    result => {
                        this.filterable = result;
                    }
                );
        },
        loadRelations(table = null) {
            $wire.loadRelations(table)
                .then(
                    result => {
                        this.relations = result;
                    }
                );
        },
        filterIndex: 0,
        newFilter: {column: '', operator: '', value: '', relation: ''},
        addFilter() {
            if (this.filters.length === 0) {
                this.filters.push([]);
                this.filterIndex = 0;
            }

            if (this.newFilter.relation) {
                this.newFilter.column = this.newFilter.relation + '.' + this.newFilter.column;
                this.newFilter.relation = '';
            }

            this.filters[this.filterIndex].push(this.newFilter);
            this.resetFilter();
            this.filterSelectType = 'text';

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
            this.filters[groupIndex].splice(this.filters[groupIndex].indexOf(index), 1);

            if(this.filters[groupIndex].length === 0) {
                this.filters.splice(this.filters.indexOf(groupIndex), 1);
            }
        },
        removeFilterGroup(index) {
            this.filters.splice(this.filters.indexOf(index), 1);
        },
        clearFilters() {
            this.filters = [];
            this.filterIndex = 0;
            $wire.sortTable('');
        },
        resetFilter() {
            this.filterSelectType = 'text';
            this.newFilter = {column: '', operator: '', value: '', relation: ''};
        },
        filterName: '',
        permanent: false,
        columns: [],
        getColumns() {
            $wire.getExportColumns().then(result => {this.columns = result})
        },
        relations: [],
        savedFilters: [],
        getSavedFilters() {
            $wire.getSavedFilters().then(result => {this.savedFilters = result})
        },
        formatter(col, record) {
            const val = _.get(record, col, null);

            if (this.intendentedCols.includes(col)) {
                return `<span class='${ record.depth >= 1 ? 'indent-icon' : '' }' style='text-indent:${ record.depth * 10 }px;'>` + val + '</span>';
            }

            if (this.formatters.hasOwnProperty(col)) {
                const type = this.formatters[col];

                return formatters.format({value: val, type: type, context: record});
            }

            return val;
        },
        disabled() {
            return false;
        },
    })
    )
});

window.formatters = {
    format: function ({value, type, options, context}) {
        if (value === null) {
            return value;
        }

        if (typeof type === 'object') {
            options = type[1];
            type = type[0];
        }

        if (this[type]) {
            return this[type](value, options, context);
        }

        return value;
    },
    money: (value, currency = null, context) => {
        if (value === null) {
            return value;
        }

        const documentCurrencyCode = document.querySelector('meta[name="currency-code"]')?.getAttribute('content');

        let currencyCode;

        if (currency === null && documentCurrencyCode) {
            currencyCode = documentCurrencyCode;
        } else if (typeof currency === 'string') {
            currencyCode = currency;
        } else if (typeof currency === 'object' && currency.hasOwnProperty('property')) {
            currencyCode = context[currency.property];
        } else if (
            typeof currency === 'object'
            && currency.hasOwnProperty('currency')
            && currency.currency.hasOwnProperty('iso')
        ) {
            currencyCode = currency.currency.iso;
        } else if (typeof currency === 'object' && currency.hasOwnProperty('iso')) {
            currencyCode = currency.iso;
        } else {
            currencyCode = documentCurrencyCode;
        }

        if (! (typeof currencyCode === 'string')) {
            return formatters.float(value);
        }

        try {
            return new Intl.NumberFormat(document.documentElement.lang, {
                style: 'currency',
                currency: currencyCode,
                minimumFractionDigits: 2,
            }).format(value);
        } catch (e) {
            return formatters.float(value) + ' ' + currencyCode;
        }
    },
    percentage: (value) => {
        const percentageFormatter = new Intl.NumberFormat(document.documentElement.lang, {
            style: 'percent',
            minimumFractionDigits: 2,
        });

        return percentageFormatter.format(value);
    },
    bool: (value) => {
        if (value === 'false' || value === false || value === 0 || value === '0' || value === null) {
            return `<span class="outline-none inline-flex justify-center items-center group rounded-full w-6 h-6 text-white bg-negative-500 dark:bg-negative-700">
                        <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </span>`;
        } else {
            return `<span class="outline-none inline-flex justify-center items-center group rounded-full w-6 h-6 text-white bg-positive-500 dark:bg-positive-700">
                    <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </span>`;
        }
    },
    array: (value) => {
        if (typeof value === 'array'){
            return value.join(', ');
        }

        return value;
    },
    object: (value) => {
        return JSON.stringify(value);
    },
    boolean: (value) => {
        return formatters.bool(value);
    },
    date: (value) => {
        return new Date(value).toLocaleDateString(document.documentElement.lang);
    },
    datetime: (value) => {
        return new Date(value).toLocaleString(document.documentElement.lang);
    },
    relativeTime: (value) => {
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
    time: (value) => {
        return new Date(value).toLocaleTimeString(document.documentElement.lang);
    },
    float: (value) => {
        if (isNaN(parseFloat(value))) {
            return value;
        }

        return parseFloat(value).toLocaleString(document.documentElement.lang);
    },
    int: (value) => {
        return parseInt(value);
    },
    string: (value) => {
        if (value === null) {
            return value;
        }

        return value.toString();
    },
    state: (value, colors) => {
        const color = colors[value];

        return '<span class="outline-none inline-flex justify-center items-center group rounded gap-x-1 text-xs font-semibold px-2.5 py-0.5 text-' + color + '-600 bg-' + color + '-100 dark:text-' + color + '-400 dark:bg-slate-700">\n' +
            value + '\n' +
            '</span>';
    },
    image: (value) => {
        if (! value) {
            return value;
        }

        return '<div class="shrink-0 inline-flex items-center justify-center overflow-hidden rounded-full border border-gray-200 dark:border-secondary-500 dark:bg-gray-200">\n' +
            '    \n' +
            '            <img class="shrink-0 object-contain object-center rounded-full w-8 h-8 text-xl" src="' + value + '">\n' +
            '    \n' +
            '    </div>';
    },
    email: (value) => {
        return '<a href="mailto:' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    url: (value) => {
        return '<a href="' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    tel: (value) => {
        return '<a href="tel:' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    link: (value) => {
        return '<a href="' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    inputType: (value) => {
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
    }
}
