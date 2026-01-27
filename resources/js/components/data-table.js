export default function data_table($wire) {
    return {
        async init() {
            this.loadTableConfig();
            this.$nextTick(() => {
                this.$watch('enabledCols', () => {
                    $wire.storeColLayout(this.enabledCols);
                    $wire.getFormatters().then((formatters) => {
                        this.formatters = formatters;
                    });
                    $wire.getColLabels(this.enabledCols).then((result) => {
                        this.colLabels = result;
                    });
                });
            });
            this.loadFilterable();
            this.loadRelationTableFields('');

            this.$watch('search', () => {
                $wire.startSearch();
            });

            this.$watch('aggregatableCols', () => {
                $wire.applyAggregations();
            });

            this.$watch('newFilter.column', () => {
                if (!Boolean(this.newFilter.column)) {
                    return;
                }

                let valueList = this.filterValueLists.hasOwnProperty(
                    this.newFilter.column,
                );

                if (valueList) {
                    this.filterSelectType = 'valueList';
                    this.newFilter.operator = '=';
                }
            });

            this.$watch('newFilter.operator', () => {
                if (
                    this.newFilter.operator === 'is null' ||
                    this.newFilter.operator === 'is not null'
                ) {
                    this.filterSelectType = 'none';
                }
            });

            this.$watch('newFilter.relation', () => {
                this.newFilter.column = '';
                this.loadRelationTableFields(this.newFilter.relation);
            });

            this.$watch('selected', () => {
                this.$dispatch('tall-datatables-selected', this.selected);
            });

            if (window.Echo !== undefined) {
                this.$watch('broadcastChannels', (newChannels, oldChannels) => {
                    const removedChannels = Object.values(oldChannels).filter(
                        (channel) =>
                            !Object.values(newChannels).includes(channel),
                    );
                    const addedChannels = Object.values(newChannels).filter(
                        (channel) =>
                            !Object.values(oldChannels).includes(channel),
                    );

                    removedChannels.forEach((channel) => {
                        Echo.leave(channel);
                    });

                    addedChannels.forEach((channel) => {
                        Echo.private(channel).listenToAll((event, data) => {
                            $wire.eloquentEventOccurred(event, data);
                        });
                    });
                });
            }

            // Event delegation for pagination buttons in grouped view
            this.$el.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;

                e.stopPropagation();
                const action = btn.dataset.action;

                if (action === 'prev-group-page' || action === 'next-group-page') {
                    const groupKey = btn.dataset.groupKey;
                    const page = parseInt(btn.dataset.page, 10);
                    this.setGroupPage(groupKey, page);
                } else if (
                    action === 'prev-groups-page' ||
                    action === 'next-groups-page'
                ) {
                    const page = parseInt(btn.dataset.page, 10);
                    this.setGroupsPage(page);
                }
            });
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

            const searchLower = search.toLowerCase();

            // data could be an object or an array, search in both
            // if its an object we have to return an object
            if (typeof data === 'object' && !Array.isArray(data)) {
                let obj = {};
                for (const [key, value] of Object.entries(data)) {
                    // Search in value and in translated label
                    const label = this.getLabel(value) || '';
                    if (
                        JSON.stringify(value).toLowerCase().includes(searchLower) ||
                        label.toString().toLowerCase().includes(searchLower)
                    ) {
                        obj[key] = value;
                    }
                }

                return obj;
            }

            // its an array, return all items that include the search string
            return data.filter((item) => {
                // Search in item and in translated label
                const label = this.getLabel(item) || '';
                return (
                    JSON.stringify(item).toLowerCase().includes(searchLower) ||
                    label.toString().toLowerCase().includes(searchLower)
                );
            });
        },
        loadTableConfig() {
            $wire.getConfig().then((result) => {
                this.enabledCols = result.enabledCols;
                this.availableCols = result.availableCols;
                this.sortable = result.sortable;
                this.aggregatable = result.aggregatable;
                this.groupable = result.groupable;
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
                this.groupLabels = result.groupLabels;
            });
        },
        data: $wire.entangle('data').live,
        enabledCols: [],
        availableCols: [],
        colLabels: [],
        operatorLabels: [],
        groupLabels: {},
        sortable: [],
        aggregatable: [],
        groupable: [],
        groupBy: $wire.entangle('groupBy'),
        expandedGroups: $wire.entangle('expandedGroups'),
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
            if (!filter) {
                return;
            }

            const label = this.getLabel(filter.column) ?? filter.column;
            let value =
                this.filterValueLists[filter.column]?.find((item) => {
                    return item.value == filter.value;
                })?.label ?? filter.value;

            if (Array.isArray(value)) {
                value = filter.value
                    .map((item) => {
                        if (item.hasOwnProperty('calculation')) {
                            return this.getCalculationLabel(item.calculation);
                        }

                        return formatters.format({ value: item });
                    })
                    .join(' ' + this.operatorLabels.and + ' ');
            } else {
                value = formatters.format({ value: value });
            }

            return (
                label +
                ' ' +
                (this.operatorLabels[filter.operator] || filter.operator) +
                ' ' +
                value
            );
        },
        getCalculationLabel(calculation) {
            if (!calculation) {
                return;
            }

            let label = this.getLabel('Now');

            if (calculation.value !== 0) {
                label =
                    label +
                    ' ' +
                    calculation.operator +
                    ' ' +
                    calculation.value +
                    ' ' +
                    this.getLabel(calculation.unit);
            }

            if (calculation.is_start_of) {
                label =
                    label +
                    ' ' +
                    this.getLabel('Start of') +
                    ' ' +
                    this.getLabel(calculation.start_of);
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
        isGrouped() {
            return Boolean(this.groupBy) && this.data && this.data.hasOwnProperty('groups');
        },
        getGroups() {
            return this.data.groups ?? [];
        },
        /**
         * Returns a flat array of rows for grouped view.
         * Each row has a 'rowType' property: 'group-header', 'data', or 'pagination'.
         * This allows using a single x-for loop in the template.
         */
        getFlatGroupedRows() {
            const groups = this.getGroups();
            const rows = [];

            for (const group of groups) {
                // Add group header row
                rows.push({
                    rowType: 'group-header',
                    group: group,
                    _key: 'header-' + group.key,
                });

                // Add data rows (only for expanded groups)
                if (this.isGroupExpanded(group.key) && group.data) {
                    for (let i = 0; i < group.data.length; i++) {
                        rows.push({
                            rowType: 'data',
                            group: group,
                            record: group.data[i],
                            index: i,
                            _key: 'data-' + group.key + '-' + (group.data[i].id ?? i),
                        });
                    }
                }

                // Add pagination row for data within group if needed
                if (
                    this.isGroupExpanded(group.key) &&
                    group.pagination &&
                    group.pagination.last_page > 1
                ) {
                    rows.push({
                        rowType: 'pagination',
                        group: group,
                        _key: 'pagination-' + group.key,
                    });
                }
            }

            // Add groups pagination row at the end if there are multiple pages of groups
            const groupsPagination = this.getGroupsPagination();
            if (groupsPagination && groupsPagination.last_page > 1) {
                rows.push({
                    rowType: 'groups-pagination',
                    pagination: groupsPagination,
                    _key: 'groups-pagination',
                });
            }

            return rows;
        },
        /**
         * Returns the correct plural form based on count.
         * Expects format "singular|plural" like Laravel's trans_choice.
         */
        transChoice(key, count) {
            const translation = this.groupLabels[key] || key;
            const parts = translation.split('|');
            if (parts.length === 2) {
                return count === 1 ? parts[0] : parts[1];
            }
            return translation;
        },
        /**
         * Renders the complete HTML content for a grouped row based on its type.
         * This handles all row types: group-header, data, pagination, and groups-pagination.
         */
        renderGroupedRow(row) {
            const labels = this.groupLabels;

            if (row.rowType === 'group-header') {
                const isExpanded = this.isGroupExpanded(row.group.key);
                const entriesLabel = this.transChoice('entries', row.group.count);
                const hasAggregates =
                    row.group.aggregates &&
                    Object.keys(row.group.aggregates).length > 0;

                // If we have aggregates, render cells per column
                if (hasAggregates) {
                    const aggTypeLabels = {
                        sum: labels.sum || 'Sum',
                        avg: labels.avg || 'Avg',
                        min: labels.min || 'Min',
                        max: labels.max || 'Max',
                    };

                    // First cell: group label with expand arrow
                    let html = `<td class='border-b border-slate-300 px-3 py-3 text-sm whitespace-nowrap dark:border-slate-500'>
                        <div class='flex items-center gap-3'>
                            <svg class='h-5 w-5 transform transition-transform duration-200 ${isExpanded ? 'rotate-90' : ''}' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='currentColor'>
                                <path fill-rule='evenodd' d='M16.28 11.47a.75.75 0 0 1 0 1.06l-7.5 7.5a.75.75 0 0 1-1.06-1.06L14.69 12 7.72 5.03a.75.75 0 0 1 1.06-1.06l7.5 7.5Z' clip-rule='evenodd'/>
                            </svg>
                            <span class='font-semibold'>${row.group.label}</span>
                            <span class='inline-flex items-center justify-center gap-x-1 rounded-full border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300'>${row.group.count} ${entriesLabel}</span>
                        </div>
                    </td>`;

                    // Render cells for each enabled column with aggregate values
                    for (const col of this.enabledCols) {
                        const isSticky = this.stickyCols.includes(col);
                        const stickyClass = isSticky
                            ? 'sticky left-0 border-r bg-gray-100 dark:bg-secondary-700'
                            : '';
                        const stickyStyle = isSticky ? 'z-index: 2' : '';

                        // Collect all aggregate values for this column
                        let cellContent = '';
                        for (const [aggType, columns] of Object.entries(
                            row.group.aggregates,
                        )) {
                            if (columns && columns.hasOwnProperty(col)) {
                                const formattedValue = this.formatter(col, {
                                    [col]: columns[col],
                                });
                                cellContent += `<div class='flex items-center gap-1'>
                                    <span class='font-semibold text-slate-500 dark:text-slate-400'>${aggTypeLabels[aggType]}:</span>
                                    <span>${formattedValue}</span>
                                </div>`;
                            }
                        }

                        html += `<td class="border-b border-slate-300 dark:border-slate-500 whitespace-nowrap px-3 py-3 text-sm ${stickyClass}" style="${stickyStyle}">
                            ${cellContent}
                        </td>`;
                    }

                    // Last empty cell for actions column
                    html += `<td class='table-cell border-b border-slate-300 px-3 py-3 text-sm whitespace-nowrap dark:border-slate-500'></td>`;

                    return html;
                }

                // No aggregates: use colspan for simple header
                return `<td colspan='100%' class='border-b border-slate-300 px-3 py-3 text-sm dark:border-slate-500'>
                    <div class='flex items-center gap-3'>
                        <svg class='h-5 w-5 transform transition-transform duration-200 ${isExpanded ? 'rotate-90' : ''}' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='currentColor'>
                            <path fill-rule='evenodd' d='M16.28 11.47a.75.75 0 0 1 0 1.06l-7.5 7.5a.75.75 0 0 1-1.06-1.06L14.69 12 7.72 5.03a.75.75 0 0 1 1.06-1.06l7.5 7.5Z' clip-rule='evenodd'/>
                        </svg>
                        <span class='font-semibold'>${row.group.label}</span>
                        <span class='inline-flex items-center justify-center gap-x-1 rounded-full border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300'>${row.group.count} ${entriesLabel}</span>
                    </div>
                </td>`;
            }

            if (row.rowType === 'data') {
                return `<td class='max-w-0 border-b border-slate-200 text-sm whitespace-nowrap dark:border-slate-600'></td>` +
                    this.renderGroupedDataCells(row.record) +
                    `<td class='table-cell border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap dark:border-slate-600'></td>`;
            }

            if (row.rowType === 'pagination' && row.group.pagination) {
                const p = row.group.pagination;
                const groupKey = row.group.key;
                return `<td colspan='100%' class='px-4 py-3 sm:px-6'>
                    <div class='flex items-center justify-between'>
                        <div class='flex items-center gap-1 text-sm text-slate-400'>
                            ${labels.showing || 'Showing'}
                            <span class='font-medium'>${p.from}</span>
                            ${labels.to || 'to'}
                            <span class='font-medium'>${p.to}</span>
                            ${labels.of || 'of'}
                            <span class='font-medium'>${p.total}</span>
                        </div>
                        <nav class='isolate inline-flex space-x-1 rounded-md shadow-sm' aria-label='Pagination'>
                            <button type='button' class='soft-scrollbar inline-flex items-center justify-center gap-x-1 rounded-md border border-secondary-200 bg-white text-sm text-secondary-600 ring-secondary-200 outline-hidden transition-colors duration-100 ease-linear hover:bg-secondary-100 focus:ring-2 dark:border-secondary-500 dark:bg-secondary-600 dark:text-secondary-200 dark:ring-secondary-400 dark:hover:bg-secondary-500 dark:focus:ring-offset-secondary-800 disabled:cursor-not-allowed disabled:opacity-50 px-2.5 py-1.5' ${p.current_page <= 1 ? 'disabled' : ''} data-action='prev-group-page' data-group-key='${groupKey}' data-page='${p.current_page - 1}'>
                                <svg class='h-4 w-4' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 19.5 8.25 12l7.5-7.5'/></svg>
                            </button>
                            <span class='inline-flex items-center px-3 py-1.5 text-sm text-secondary-600 dark:text-secondary-300'>${p.current_page} / ${p.last_page}</span>
                            <button type='button' class='soft-scrollbar inline-flex items-center justify-center gap-x-1 rounded-md border border-secondary-200 bg-white text-sm text-secondary-600 ring-secondary-200 outline-hidden transition-colors duration-100 ease-linear hover:bg-secondary-100 focus:ring-2 dark:border-secondary-500 dark:bg-secondary-600 dark:text-secondary-200 dark:ring-secondary-400 dark:hover:bg-secondary-500 dark:focus:ring-offset-secondary-800 disabled:cursor-not-allowed disabled:opacity-50 px-2.5 py-1.5' ${p.current_page >= p.last_page ? 'disabled' : ''} data-action='next-group-page' data-group-key='${groupKey}' data-page='${p.current_page + 1}'>
                                <svg class='h-4 w-4' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='m8.25 4.5 7.5 7.5-7.5 7.5'/></svg>
                            </button>
                        </nav>
                    </div>
                </td>`;
            }

            if (row.rowType === 'groups-pagination') {
                const p = row.pagination;
                const from = (p.current_page - 1) * p.per_page + 1;
                const to = Math.min(p.current_page * p.per_page, p.total);
                return `<td colspan='100%' class='px-4 py-3 sm:px-6'>
                    <div class='flex items-center justify-between'>
                        <div class='flex items-center gap-1 text-sm text-slate-400'>
                            ${labels.groups || 'Groups'}
                            <span class='font-medium'>${from}</span>
                            -
                            <span class='font-medium'>${to}</span>
                            ${labels.of || 'of'}
                            <span class='font-medium'>${p.total}</span>
                        </div>
                        <nav class='isolate inline-flex space-x-1 rounded-md shadow-sm' aria-label='Pagination'>
                            <button type='button' class='soft-scrollbar inline-flex items-center justify-center gap-x-1 rounded-md border border-secondary-200 bg-white text-sm text-secondary-600 ring-secondary-200 outline-hidden transition-colors duration-100 ease-linear hover:bg-secondary-100 focus:ring-2 dark:border-secondary-500 dark:bg-secondary-600 dark:text-secondary-200 dark:ring-secondary-400 dark:hover:bg-secondary-500 dark:focus:ring-offset-secondary-800 disabled:cursor-not-allowed disabled:opacity-50 px-2.5 py-1.5' ${p.current_page <= 1 ? 'disabled' : ''} data-action='prev-groups-page' data-page='${p.current_page - 1}'>
                                <svg class='h-4 w-4' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 19.5 8.25 12l7.5-7.5'/></svg>
                            </button>
                            <span class='inline-flex items-center px-3 py-1.5 text-sm text-secondary-600 dark:text-secondary-300'>${p.current_page} / ${p.last_page}</span>
                            <button type='button' class='soft-scrollbar inline-flex items-center justify-center gap-x-1 rounded-md border border-secondary-200 bg-white text-sm text-secondary-600 ring-secondary-200 outline-hidden transition-colors duration-100 ease-linear hover:bg-secondary-100 focus:ring-2 dark:border-secondary-500 dark:bg-secondary-600 dark:text-secondary-200 dark:ring-secondary-400 dark:hover:bg-secondary-500 dark:focus:ring-offset-secondary-800 disabled:cursor-not-allowed disabled:opacity-50 px-2.5 py-1.5' ${p.current_page >= p.last_page ? 'disabled' : ''} data-action='next-groups-page' data-page='${p.current_page + 1}'>
                                <svg class='h-4 w-4' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='m8.25 4.5 7.5 7.5-7.5 7.5'/></svg>
                            </button>
                        </nav>
                    </div>
                </td>`;
            }

            return '';
        },
        /**
         * Renders all cells for a data row in the grouped view.
         * This is needed because we can't use nested x-for templates inside <tr>.
         */
        renderGroupedDataCells(record) {
            let html = '';
            const hasHref = record.href && !record.deleted_at;

            for (const col of this.enabledCols) {
                const isSticky = this.stickyCols.includes(col);
                const stickyClass = isSticky
                    ? 'sticky left-0 border-r bg-white dark:bg-secondary-800 dark:text-gray-50'
                    : '';
                const stickyStyle = isSticky ? 'z-index: 2' : '';
                const cellContent = this.formatter(col, record);
                const leftContent =
                    this.leftAppend[col] ?
                        this.formatter(this.leftAppend[col], record)
                    :   '';
                const rightContent =
                    this.rightAppend[col] ?
                        this.formatter(this.rightAppend[col], record)
                    :   '';
                const topContent =
                    this.topAppend[col] ?
                        this.formatter(this.topAppend[col], record)
                    :   '';
                const bottomContent =
                    this.bottomAppend[col] ?
                        this.formatter(this.bottomAppend[col], record)
                    :   '';

                // td has p-0, link/div gets the padding
                html += `<td class="border-b border-slate-200 dark:border-slate-600 whitespace-nowrap max-w-xs overflow-hidden text-ellipsis text-sm p-0 ${stickyClass}" style="${stickyStyle}">`;

                // Wrap content in <a> tag - always present, href only when available
                const cursorClass = hasHref ? 'cursor-pointer' : '';
                const hrefAttr = hasHref ? `href="${record.href}"` : '';
                html += `<a ${hrefAttr} class="block px-3 py-4 ${cursorClass}" wire:navigate>`;

                html += '<div class="flex flex-wrap gap-1.5">';

                if (leftContent) {
                    html += `<div class="flex flex-wrap gap-1">${leftContent}</div>`;
                }

                html += '<div class="grow">';
                if (topContent) {
                    html += `<div class="flex flex-wrap gap-1">${topContent}</div>`;
                }
                html += `<div class="flex flex-wrap gap-1">${cellContent}</div>`;
                if (bottomContent) {
                    html += `<div class="flex flex-wrap gap-1">${bottomContent}</div>`;
                }
                html += '</div>';

                if (rightContent) {
                    html += `<div class="flex flex-wrap gap-1">${rightContent}</div>`;
                }

                html += '</div></a></td>';
            }
            return html;
        },
        toggleGroup(groupKey) {
            $wire.toggleGroup(groupKey);
        },
        isGroupExpanded(groupKey) {
            return this.expandedGroups.includes(groupKey);
        },
        setGroupPage(groupKey, page) {
            $wire.setGroupPage(groupKey, page);
        },
        setGroupsPage(page) {
            $wire.setGroupsPage(page);
        },
        getGroupsPagination() {
            return this.data?.groups_pagination ?? null;
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
                } else if (Boolean(this.newFilter.operator)) {
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
            $wire.resetLayout().then(() => {
                this.loadTableConfig();
            });
        },
        getLabel(col) {
            if (!col) {
                return '';
            }

            return (
                this.colLabels?.[col] ||
                this.groupLabels?.[col] ||
                col?.label ||
                this.relationColLabels?.[col] ||
                this.operatorLabels?.[col] ||
                col
            );
        },
        getFilterInputType(col) {
            if (!col || col === '.') {
                return 'text';
            }

            let splittedCol = col.split('.');
            let table = 'self';

            if (splittedCol.length > 1) {
                table = splittedCol[0] || 'self';
                col = splittedCol[1];
            }

            const formatter = this.relationFormatters?.[table]?.[col] ?? null;

            return formatters.inputType(formatter);
        },
        loadRelationTableFields(table = null) {
            let tableAlias = table;
            if (table === '') {
                tableAlias = 'self';
            }

            if (this.relationTableFields.hasOwnProperty(tableAlias)) {
                return;
            }

            $wire.getRelationTableCols(table).then((result) => {
                this.relationTableFields[tableAlias] = Object.keys(result);
                this.relationFormatters[tableAlias] = result;
                $wire
                    .getColLabels(this.relationTableFields[tableAlias])
                    .then((result) => {
                        Object.assign(this.relationColLabels, result);
                    });

                if (!this.textFilter) {
                    this.textFilter = result.reduce((acc, curr) => {
                        acc[curr] = '';
                        return acc;
                    }, {});
                    this.$watch('textFilter', () => {
                        this.parseFilter();
                    });
                }
            });
        },
        loadFilterable(table = null) {
            $wire.getFilterableColumns(table).then((result) => {
                this.filterable = result;

                if (!this.textFilter) {
                    this.textFilter = result.reduce((acc, curr) => {
                        acc[curr] = '';
                        return acc;
                    }, {});
                    this.$watch('textFilter', () => {
                        this.parseFilter();
                    });
                }
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
        addCalculation(index) {
            // check if the index exists, otherwise add it
            if (!this.newFilter.value[index]) {
                this.newFilter.value[index] = {};
            }

            this.newFilter.value[index].calculation = this.newFilterCalculation;
            this.newFilterCalculation = {
                value: 0,
                operator: '-',
                unit: 'days',
                is_start_of: '',
                start_of: 'day',
            };
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
                    operator = '=';
                } else {
                    operator = value.match(
                        /^(>=|<=|!=|=|like|not like|>|<|is null|is not null)/i,
                    );
                }

                if (
                    !operator &&
                    (this.formatters[key] === 'date' ||
                        this.formatters[key] === 'datetime') &&
                    filterValue.length > 6 &&
                    new Date(filterValue) !== 'Invalid Date' &&
                    !isNaN(new Date(filterValue))
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
                if (
                    !value.trim().startsWith('%') &&
                    !value.trim().endsWith('%')
                ) {
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

            newFilter.operator = Boolean(newFilter.operator)
                ? newFilter.operator
                : '=';
            if (newFilter.relation && newFilter.relation !== '0') {
                newFilter.column = newFilter.relation + '.' + newFilter.column;
                newFilter.relation = '';
            }

            this.filters[this.filterIndex].push(newFilter);
            this.resetFilter();
            this.filterSelectType = 'text';
            $wire.getColLabels().then((result) => {
                this.colLabels = result;
            });

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
                        this.removeFilterGroup(groupIndex);
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
            this.newFilter = {
                column: '',
                operator: '',
                value: [],
                relation: '',
            };
        },
        filterName: '',
        permanent: false,
        withEnabledCols: true,
        exportColumns: [],
        exportableColumns: [],
        getColumns() {
            $wire.getExportableColumns().then((result) => {
                this.exportableColumns = result;
                this.exportColumns = this.enabledCols;
            });
        },
        relations: [],
        savedFilters: [],
        getSavedFilters() {
            $wire.getSavedFilters().then((result) => {
                this.savedFilters = result;
            });
        },
        loadSavedFilter() {
            $wire.loadSavedFilter().then((result) => {
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
                label =
                    this.filterValueLists[col].find((item) => {
                        return item.value == val;
                    })?.label ?? val;
            }

            if (this.formatters.hasOwnProperty(col)) {
                let type = this.formatters[col];
                return formatters
                    .setLabel(label)
                    .format({ value: val, type: type, context: record });
            } else {
                return formatters
                    .setLabel(label)
                    .format({ value: val, context: record });
            }
        },
    };
}
