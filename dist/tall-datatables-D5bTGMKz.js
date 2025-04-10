function f(e) {
    return {
        async init() {
            this.loadTableConfig(),
                this.$nextTick(() => {
                    this.$watch('enabledCols', () => {
                        e.storeColLayout(this.enabledCols),
                            e.getFormatters().then((t) => {
                                this.formatters = t;
                            }),
                            e.getColLabels(this.enabledCols).then((t) => {
                                this.colLabels = t;
                            });
                    });
                }),
                this.loadFilterable(),
                this.$watch('search', () => {
                    e.startSearch();
                }),
                this.$watch('aggregatableCols', () => {
                    e.applyAggregations();
                }),
                this.$watch('newFilter.column', () => {
                    if (!this.newFilter.column) return;
                    this.filterValueLists.hasOwnProperty(
                        this.newFilter.column,
                    ) &&
                        ((this.filterSelectType = 'valueList'),
                        (this.newFilter.operator = '='));
                }),
                this.$watch('newFilter.operator', () => {
                    (this.newFilter.operator === 'is null' ||
                        this.newFilter.operator === 'is not null') &&
                        (this.filterSelectType = 'none');
                }),
                this.$watch('newFilter.relation', () => {
                    (this.newFilter.column = ''),
                        this.loadRelationTableFields(this.newFilter.relation);
                }),
                this.$watch('selected', () => {
                    this.$dispatch('tall-datatables-selected', this.selected);
                }),
                window.Echo !== void 0 &&
                    this.$watch('broadcastChannels', (t, r) => {
                        const a = Object.values(r).filter(
                                (i) => !Object.values(t).includes(i),
                            ),
                            s = Object.values(t).filter(
                                (i) => !Object.values(r).includes(i),
                            );
                        a.forEach((i) => {
                            Echo.leave(i);
                        }),
                            s.forEach((i) => {
                                Echo.private(i).listenToAll((l, n) => {
                                    e.eloquentEventOccurred(l, n);
                                });
                            });
                    });
        },
        columnSortHandle(t, r) {
            const a = this.enabledCols.indexOf(t),
                [s] = this.enabledCols.splice(a, 1);
            this.enabledCols.splice(r, 0, s);
        },
        searchable(t, r = null) {
            if (!r) return t;
            if (typeof t == 'object') {
                let a = {};
                for (const [s, i] of Object.entries(t))
                    JSON.stringify(i).toLowerCase().includes(r.toLowerCase()) &&
                        (a[s] = i);
                return a;
            }
            return t.filter((a) =>
                JSON.stringify(a).toLowerCase().includes(r.toLowerCase()),
            );
        },
        loadTableConfig() {
            e.getConfig().then((t) => {
                (this.enabledCols = t.enabledCols),
                    (this.availableCols = t.availableCols),
                    (this.sortable = t.sortable),
                    (this.aggregatable = t.aggregatable),
                    (this.selectable = t.selectable),
                    (this.formatters = t.formatters),
                    (this.leftAppend = t.leftAppend),
                    (this.rightAppend = t.rightAppend),
                    (this.topAppend = t.topAppend),
                    (this.bottomAppend = t.bottomAppend),
                    (this.searchRoute = t.searchRoute),
                    (this.echoListeners = t.echoListeners),
                    (this.operatorLabels = t.operatorLabels),
                    (this.colLabels = t.colLabels);
            });
        },
        data: e.entangle('data').live,
        enabledCols: [],
        availableCols: [],
        colLabels: [],
        operatorLabels: [],
        sortable: [],
        aggregatable: [],
        selectable: !1,
        showSelectedActions: !1,
        formatters: [],
        leftAppend: [],
        rightAppend: [],
        topAppend: [],
        bottomAppend: [],
        broadcastChannels: [],
        searchRoute: '',
        tab: 'edit-filters',
        showSavedFilters: !1,
        filterValueLists: e.entangle('filterValueLists', !0),
        filters: e.entangle('userFilters', !0),
        aggregatableCols: e.entangle('aggregatableCols'),
        orderByCol: e.entangle('userOrderBy'),
        orderAsc: e.entangle('userOrderAsc'),
        stickyCols: e.entangle('stickyCols', !0),
        initialized: e.entangle('initialized', !0),
        search: e.entangle('search'),
        selected: e.entangle('selected'),
        filterBadge(t) {
            var s, i;
            if (!t) return;
            const r = this.getLabel(t.column) ?? t.column;
            let a =
                ((i =
                    (s = this.filterValueLists[t.column]) == null
                        ? void 0
                        : s.find((l) => l.value == t.value)) == null
                    ? void 0
                    : i.label) ?? t.value;
            return (
                Array.isArray(a)
                    ? (a = t.value
                          .map((l) =>
                              l.hasOwnProperty('calculation')
                                  ? this.getCalculationLabel(l.calculation)
                                  : formatters.format({ value: l }),
                          )
                          .join(' ' + this.operatorLabels.and + ' '))
                    : (a = formatters.format({ value: a })),
                r +
                    ' ' +
                    (this.operatorLabels[t.operator] || t.operator) +
                    ' ' +
                    a
            );
        },
        getCalculationLabel(t) {
            if (!t) return;
            let r = this.getLabel('Now');
            return (
                t.value !== 0 &&
                    (r =
                        r +
                        ' ' +
                        t.operator +
                        ' ' +
                        t.value +
                        ' ' +
                        this.getLabel(t.unit)),
                t.is_start_of &&
                    (r =
                        r +
                        ' ' +
                        this.getLabel('Start of') +
                        ' ' +
                        this.getLabel(t.start_of)),
                r
            );
        },
        getData() {
            return (
                (this.broadcastChannels = e.get('broadcastChannels') ?? []),
                this.data.hasOwnProperty('data') ? this.data.data : this.data
            );
        },
        filterSelectType: 'text',
        loadSidebar(t = null) {
            this.$refs.filterOperator &&
                (t
                    ? ((this.newFilter = t), (this.tab = 'edit-filters'))
                    : this.resetFilter(),
                this.getSavedFilters(),
                this.newFilter.column
                    ? this.$nextTick(() => {
                          var r;
                          return (r = this.$refs.filterOperator) == null
                              ? void 0
                              : r.focus();
                      })
                    : this.newFilter.operator
                      ? this.$nextTick(() => {
                            var r;
                            return (r = this.$refs.filterValue) == null
                                ? void 0
                                : r.focus();
                        })
                      : this.$nextTick(() => {
                            var r;
                            return (r = this.$refs.filterColumn) == null
                                ? void 0
                                : r.focus();
                        }),
                (this.showSavedFilters = !1)),
                $slideOpen('data-table-sidebar-' + e.id.toLowerCase());
        },
        filterable: [],
        relationTableFields: {},
        relationFormatters: {},
        relationColLabels: {},
        resetLayout() {
            e.resetLayout().then(() => {
                this.loadTableConfig();
            });
        },
        getLabel(t) {
            return (
                this.colLabels[t] ||
                t.label ||
                this.relationColLabels[t] ||
                this.operatorLabels[t] ||
                t
            );
        },
        getFilterInputType(t) {
            var i, l;
            if (!t || t === '.') return 'text';
            let r = t.split('.'),
                a = 'self';
            r.length > 1 && ((a = r[0] || 'self'), (t = r[1]));
            const s =
                ((l = (i = this.relationFormatters) == null ? void 0 : i[a]) ==
                null
                    ? void 0
                    : l[t]) ?? null;
            return formatters.inputType(s);
        },
        loadRelationTableFields(t = null) {
            let r = t;
            t === '' && (r = 'self'),
                !this.relationTableFields.hasOwnProperty(r) &&
                    e.getRelationTableCols(t).then((a) => {
                        (this.relationTableFields[r] = Object.keys(a)),
                            (this.relationFormatters[r] = a),
                            e
                                .getColLabels(this.relationTableFields[r])
                                .then((s) => {
                                    Object.assign(this.relationColLabels, s);
                                }),
                            this.textFilter ||
                                ((this.textFilter = a.reduce(
                                    (s, i) => ((s[i] = ''), s),
                                    {},
                                )),
                                this.$watch('textFilter', () => {
                                    this.parseFilter();
                                }));
                    });
        },
        loadFilterable(t = null) {
            e.getFilterableColumns(t).then((r) => {
                (this.filterable = r),
                    this.textFilter ||
                        ((this.textFilter = r.reduce(
                            (a, s) => ((a[s] = ''), a),
                            {},
                        )),
                        this.$watch('textFilter', () => {
                            this.parseFilter();
                        }));
            });
        },
        filterIndex: 0,
        textFilter: null,
        newFilter: { column: '', operator: '', value: [], relation: '' },
        newFilterCalculation: {
            value: 0,
            operator: '-',
            unit: 'days',
            is_start_of: '',
            start_of: 'day',
        },
        addCalculation(t) {
            this.newFilter.value[t] || (this.newFilter.value[t] = {}),
                (this.newFilter.value[t].calculation =
                    this.newFilterCalculation),
                (this.newFilterCalculation = {
                    value: 0,
                    operator: '-',
                    unit: 'days',
                    is_start_of: '',
                    start_of: 'day',
                });
        },
        parseFilter() {
            let t = [];
            for (const [r, a] of Object.entries(this.textFilter)) {
                if (a === '') continue;
                let s = a,
                    i = null;
                if (
                    (this.filterValueLists.hasOwnProperty(r)
                        ? (i = '=')
                        : (i = a.match(
                              /^(>=|<=|!=|=|like|not like|>|<|is null|is not null)/i,
                          )),
                    !i &&
                        (this.formatters[r] === 'date' ||
                            this.formatters[r] === 'datetime') &&
                        s.length > 6 &&
                        new Date(s) !== 'Invalid Date' &&
                        !isNaN(new Date(s)) &&
                        (i = '='),
                    i)
                ) {
                    t.push({
                        column: r,
                        operator: i[0].toLowerCase(),
                        value: a.replace(i[0], '').trim(),
                        relation: '',
                        textFilterKey: !0,
                    });
                    continue;
                }
                !a.trim().startsWith('%') &&
                    !a.trim().endsWith('%') &&
                    (s = '%' + a.trim() + '%'),
                    t.push({
                        column: r,
                        operator: 'like',
                        value: s,
                        relation: '',
                        textFilterKey: !0,
                    });
            }
            this.filters = t.length ? [t] : [];
        },
        addFilter() {
            let t = this.newFilter;
            this.filters.length === 0 &&
                (this.filters.push([]), (this.filterIndex = 0)),
                (t.operator = t.operator ? t.operator : '='),
                t.relation &&
                    t.relation !== '0' &&
                    ((t.column = t.relation + '.' + t.column),
                    (t.relation = '')),
                this.filters[this.filterIndex].push(t),
                this.resetFilter(),
                (this.filterSelectType = 'text'),
                e.getColLabels().then((r) => {
                    this.colLabels = r;
                }),
                this.$nextTick(() => this.$refs.filterColumn.focus());
        },
        addOrFilter() {
            if (this.filters[this.filters.length - 1].length === 0) {
                this.filterIndex = this.filters.length - 1;
                return;
            }
            (this.filterIndex = this.filters.length), this.filters.push([]);
        },
        removeFilter(t, r) {
            const a = this.filters[r];
            if (a && t >= 0 && t < a.length) {
                let s = a.splice(t, 1);
                s[0].textFilterKey && (this.textFilter[s[0].column] = ''),
                    a.length === 0 && this.removeFilterGroup(r);
            }
        },
        removeFilterGroup(t) {
            t >= 0 && t < this.filters.length && this.filters.splice(t, 1);
        },
        clearFilters() {
            (this.filters = []),
                (this.filterIndex = 0),
                (this.textFilter = {}),
                e.forgetSessionFilter(),
                e.sortTable('');
        },
        resetFilter() {
            (this.filterSelectType = 'text'),
                (this.newFilter = {
                    column: '',
                    operator: '',
                    value: [],
                    relation: '',
                });
        },
        filterName: '',
        permanent: !1,
        withEnabledCols: !0,
        exportColumns: [],
        exportableColumns: [],
        getColumns() {
            e.getExportableColumns().then((t) => {
                (this.exportableColumns = t),
                    (this.exportColumns = this.enabledCols);
            });
        },
        relations: [],
        savedFilters: [],
        getSavedFilters() {
            e.getSavedFilters().then((t) => {
                this.savedFilters = t;
            });
        },
        loadSavedFilter() {
            e.loadSavedFilter().then((t) => {
                this.loadTableConfig();
            });
        },
        toggleStickyCol(t) {
            this.stickyCols.includes(t)
                ? this.stickyCols.splice(this.stickyCols.indexOf(t), 1)
                : this.stickyCols.push(t);
        },
        formatter(t, r) {
            var i;
            const a = r[t] ?? null;
            let s;
            if (
                (this.filterValueLists.hasOwnProperty(t) &&
                    (s =
                        ((i = this.filterValueLists[t].find(
                            (l) => l.value == a,
                        )) == null
                            ? void 0
                            : i.label) ?? a),
                this.formatters.hasOwnProperty(t))
            ) {
                let l = this.formatters[t];
                return formatters
                    .setLabel(s)
                    .format({ value: a, type: l, context: r });
            } else
                return formatters.setLabel(s).format({ value: a, context: r });
        },
    };
}
function u() {
    return {
        label: null,
        badgeClasses: {
            primary:
                'text-primary-600 bg-primary-100 dark:text-primary-400 dark:bg-slate-700',
            secondary:
                'text-secondary-600 bg-secondary-100 dark:text-secondary-400 dark:bg-slate-700',
            slate: 'text-slate-600 bg-slate-100 dark:text-slate-400 dark:bg-slate-700',
            gray: 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-slate-700',
            zinc: 'text-zinc-600 bg-zinc-100 dark:text-zinc-400 dark:bg-slate-700',
            neutral:
                'text-neutral-600 bg-neutral-100 dark:text-neutral-400 dark:bg-slate-700',
            stone: 'text-stone-600 bg-stone-100 dark:text-stone-400 dark:bg-slate-700',
            red: 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-slate-700',
            orange: 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-slate-700',
            amber: 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-slate-700',
            yellow: 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-slate-700',
            lime: 'text-lime-600 bg-lime-100 dark:text-lime-400 dark:bg-slate-700',
            green: 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-slate-700',
            emerald:
                'text-emerald-600 bg-emerald-100 dark:text-emerald-400 dark:bg-slate-700',
            teal: 'text-teal-600 bg-teal-100 dark:text-teal-400 dark:bg-slate-700',
            cyan: 'text-cyan-600 bg-cyan-100 dark:text-cyan-400 dark:bg-slate-700',
            sky: 'text-sky-600 bg-sky-100 dark:text-sky-400 dark:bg-slate-700',
            blue: 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-slate-700',
            indigo: 'text-indigo-600 bg-indigo-100 dark:text-indigo-400 dark:bg-slate-700',
            violet: 'text-violet-600 bg-violet-100 dark:text-violet-400 dark:bg-slate-700',
            purple: 'text-purple-600 bg-purple-100 dark:text-purple-400 dark:bg-slate-700',
            fuchsia:
                'text-fuchsia-600 bg-fuchsia-100 dark:text-fuchsia-400 dark:bg-slate-700',
            pink: 'text-pink-600 bg-pink-100 dark:text-pink-400 dark:bg-slate-700',
            rose: 'text-rose-600 bg-rose-100 dark:text-rose-400 dark:bg-slate-700',
        },
        setLabel(e) {
            return (this.label = e), this;
        },
        format({ value: e, type: t, options: r, context: a }) {
            if (e === null) return e;
            if (Array.isArray(e)) return this.array(e);
            if ((typeof t == 'object' && ((r = t[1]), (t = t[0])), this[t]))
                return this[t](e, r, a);
            const s = this.guessType(e);
            return this[s] ? this[s](e, r, a) : e;
        },
        money(e, t = null, r) {
            var l, n;
            if (e === null) return e;
            const a =
                    (l = document.querySelector(
                        'meta[name="currency-code"]',
                    )) == null
                        ? void 0
                        : l.getAttribute('content'),
                s = document.body.dataset.currencyCode;
            let i;
            if (
                (t === null && s
                    ? (i = s)
                    : t === null && a
                      ? (i = a)
                      : typeof t == 'string'
                        ? (i = t)
                        : typeof t == 'object' &&
                            t != null &&
                            t.hasOwnProperty('property')
                          ? (i = r[t.property])
                          : typeof t == 'object' &&
                              t != null &&
                              t.hasOwnProperty('currency') &&
                              (n = t == null ? void 0 : t.currency) != null &&
                              n.hasOwnProperty('iso')
                            ? (i = t.currency.iso)
                            : typeof t == 'object' &&
                                t != null &&
                                t.hasOwnProperty('iso')
                              ? (i = t.iso)
                              : (i = a),
                typeof i != 'string')
            )
                return this.float(e);
            try {
                return new Intl.NumberFormat(document.documentElement.lang, {
                    style: 'currency',
                    currency: i,
                    minimumFractionDigits: 2,
                }).format(e);
            } catch {
                return this.float(e) + ' ' + i;
            }
        },
        coloredMoney(e, t = null, r) {
            const a = this.money(e, t, r);
            return e < 0
                ? `<span class="text-red-500 dark:text-red-700 font-semibold">${a}</span>`
                : `<span class="text-emerald-500 dark:text-emerald-700 font-semibold">${a}</span>`;
        },
        percentage(e) {
            return new Intl.NumberFormat(document.documentElement.lang, {
                style: 'percent',
                minimumFractionDigits: 2,
            }).format(e);
        },
        bool(e) {
            return e === 'false' ||
                e === !1 ||
                e === 0 ||
                e === '0' ||
                e === null
                ? `<span class="bg-red-500 dark:bg-red-700 group inline-flex h-6 w-6 items-center justify-center rounded-full text-white outline-none">
                        <svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </span>`
                : `<span class="bg-emerald-500 dark:bg-emerald-700 group inline-flex h-6 w-6 items-center justify-center rounded-full text-white outline-none">
                    <svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </span>`;
        },
        progressPercentage(e) {
            return `<div class="relative pt-1">
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                        <div style="width:${new Intl.NumberFormat('en-US', { style: 'percent' }).format(e)}" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500 dark:bg-indigo-700"></div>
                    </div>
                    <span>${this.percentage(e)}</span>
                </div>`;
        },
        array(e) {
            return (
                Array.isArray(e) ||
                    (e = typeof e == 'object' ? Object.values(e) : [e]),
                e
                    .map((t) => this.badge(this.format({ value: t }), 'indigo'))
                    .join(' ')
            );
        },
        object(e) {
            return Object.keys(e).every(
                (t) => !isNaN(parseInt(t)) && isFinite(t),
            )
                ? ((e = Array.from(e)), this.array(e))
                : Object.keys(e)
                      .map((t) => {
                          const r = this.guessType(e[t]),
                              a = this.format({ value: e[t], type: r });
                          return this.badge(`${t}: ${a}`, 'indigo');
                      })
                      .join(' ');
        },
        boolean(e) {
            return this.bool(e);
        },
        date(e) {
            return new Date(e).toLocaleDateString(
                document.documentElement.lang,
                { year: 'numeric', month: '2-digit', day: '2-digit' },
            );
        },
        datetime(e) {
            return new Date(e).toLocaleString(document.documentElement.lang, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },
        badge(e, t) {
            if (!e) return null;
            const r = t[e] || t;
            return (
                this.label && (e = this.label),
                `<span class="outline-none inline-flex justify-center items-center group rounded gap-x-1 text-xs font-semibold px-2.5 py-0.5 ${this.badgeClasses[r]}">
                ${e}
            </span>`
            );
        },
        relativeTime(e) {
            const r = new Date().getTime() - e,
                a = new Intl.RelativeTimeFormat(document.documentElement.lang, {
                    style: 'narrow',
                }),
                s = r / 1e3,
                i = s / 60,
                l = i / 60,
                n = l / 24,
                o = n / 7,
                d = n / 30,
                h = n / 365;
            switch (!0) {
                case s < 10:
                    return 'now';
                case s < 60:
                    return a.format(Math.round(s) * -1, 'second');
                case i < 60:
                    return a.format(Math.round(i) * -1, 'minute');
                case l < 24:
                    return a.format(Math.round(l) * -1, 'hour');
                case n < 7:
                    return a.format(Math.round(n) * -1, 'day');
                case o < 4:
                    return a.format(Math.round(o) * -1, 'week');
                case d < 12:
                    return a.format(Math.round(d) * -1, 'month');
                default:
                    return a.format(Math.round(h) * -1, 'year');
            }
        },
        time(e) {
            if (
                typeof e == 'string' &&
                e.match(/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/)
            )
                return e;
            let t = e;
            e = Math.abs(e);
            let r = Math.floor(e / 1e3),
                a = Math.floor(r / 60);
            r = r % 60;
            let s = Math.floor(a / 60);
            return (
                (a = a % 60),
                (s = s.toString().padStart(2, '0')),
                (a = a.toString().padStart(2, '0')),
                (r = r.toString().padStart(2, '0')),
                (t < e ? '-' : '') + `${s}:${a}:${r}`
            );
        },
        float(e) {
            if (isNaN(parseFloat(e))) return e;
            let t = parseFloat(e);
            try {
                return t.toLocaleString(document.documentElement.lang);
            } catch {
                return t;
            }
        },
        coloredFloat(e) {
            return this.coloredMoney(e, '');
        },
        int(e) {
            return parseInt(e);
        },
        string(e) {
            return e === null
                ? e
                : (this.label && (e = this.label), e.toString());
        },
        state(e, t) {
            return this.badge(e, t);
        },
        image(e) {
            return (
                e &&
                `<div class="dark:border-secondary-500 inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-200 dark:bg-gray-200">
    
            <img class="h-8 w-8 shrink-0 rounded-full object-contain object-center text-xl" src="` +
                    e +
                    `">
    
    </div>`
            );
        },
        email(e) {
            return (
                '<a href="mailto:' +
                e +
                '" class="text-indigo-500 dark:text-indigo-400">' +
                e +
                '</a>'
            );
        },
        url(e) {
            return (
                '<a href="' +
                e +
                '" class="text-indigo-500 dark:text-indigo-400">' +
                e +
                '</a>'
            );
        },
        tel(e) {
            return (
                '<a href="tel:' +
                e +
                '" class="text-indigo-500 dark:text-indigo-400">' +
                e +
                '</a>'
            );
        },
        link(e) {
            return (
                '<a href="' +
                e +
                '" class="text-indigo-500 dark:text-indigo-400">' +
                e +
                '</a>'
            );
        },
        inputType(e) {
            switch (e) {
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
        guessType(e) {
            return e === null
                ? 'null'
                : Array.isArray(e)
                  ? 'array'
                  : typeof e == 'object'
                    ? 'object'
                    : typeof e == 'boolean'
                      ? 'boolean'
                      : typeof e == 'string'
                        ? e.includes('://')
                            ? 'url'
                            : /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)
                              ? 'email'
                              : e.match(
                                      /^\d{4}-\d{2}-\d{2}(T|\s)\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})?$/,
                                  )
                                ? 'datetime'
                                : e.match(/^\d{4}-\d{2}-\d{2}$/)
                                  ? 'date'
                                  : e.match(/^\d{2}:\d{2}:\d{2}$/)
                                    ? 'time'
                                    : e.match(/^\d+$/)
                                      ? 'int'
                                      : e.match(/^\d+\.\d+$/)
                                        ? 'float'
                                        : 'string'
                        : typeof e == 'number'
                          ? e % 1 === 0
                              ? 'int'
                              : 'float'
                          : 'string';
        },
    };
}
window.formatters = u();
document.addEventListener('alpine:init', () => {
    window.Alpine.data('data_table', f);
});
