export default function data_table() {
    return {
        showSelectedActions: false,
        textFilterRows: [0],
        extraInputs: {},
        _echoChannels: [],
        _disposers: [],

        get stickyCols() {
            return this.$wire.stickyCols;
        },

        init() {
            this.textFilterRows = this._computeTextFilterRows();

            this._disposers.push(
                this.$watch(() => JSON.stringify(this.$wire.textFilters), () => {
                    const serverRows = this._computeTextFilterRows();
                    if (serverRows.length < this.textFilterRows.length) {
                        this.textFilterRows = serverRows;
                    }
                }),
            );

            this._setupEchoListeners();
        },

        destroy() {
            this._disposers.forEach((dispose) => {
                try {
                    dispose();
                } catch (e) {
                    // ignore
                }
            });
            this._disposers = [];
            this._leaveAllChannels();
        },

        _computeTextFilterRows() {
            const tf = this.$wire.textFilters || {};
            const keys = Object.keys(tf).filter((k) => !isNaN(k));
            return keys.length > 0
                ? keys.map(Number).sort((a, b) => a - b)
                : [0];
        },

        addTextFilterRow() {
            const next =
                this.textFilterRows.length > 0
                    ? Math.max(...this.textFilterRows) + 1
                    : 0;
            this.textFilterRows = [...this.textFilterRows, next];
        },

        removeTextFilterRow(index) {
            this.textFilterRows = this.textFilterRows.filter(
                (i) => i !== index,
            );
            if (this.textFilterRows.length === 0) this.textFilterRows = [0];
            this.$wire.removeTextFilterRow(index);
        },

        getInputCount(rowIndex, col) {
            const tf = (this.$wire.textFilters || {})[rowIndex] || {};
            const val = tf[col];
            const serverCount = Array.isArray(val) ? val.length : val ? 1 : 1;
            const localCount = this.extraInputs[rowIndex + '-' + col] || 0;
            return Math.max(serverCount, 1 + localCount);
        },

        addColumnInput(rowIndex, col) {
            const key = rowIndex + '-' + col;
            this.extraInputs[key] = (this.extraInputs[key] || 0) + 1;
            this.extraInputs = { ...this.extraInputs };
        },

        removeColumnInput(rowIndex, col, valueIndex) {
            this.$wire.setTextFilter(col, '', rowIndex, valueIndex);
            const key = rowIndex + '-' + col;
            if (this.extraInputs[key] > 0) {
                this.extraInputs[key]--;
                this.extraInputs = { ...this.extraInputs };
            }
        },

        getTextFilterValue(rowIndex, col, valueIndex) {
            const tf = (this.$wire.textFilters || {})[rowIndex] || {};
            const val = tf[col];
            if (Array.isArray(val)) return val[valueIndex] || '';
            return valueIndex === 0 ? val || '' : '';
        },

        /**
         * A column only obeys a width once the table itself has a definite one.
         * While the table stays at `width: auto` the fixed layout keeps sizing
         * on content, so a width set on a cell can widen a column and never
         * narrow it. Every column therefore carries a width here and the table
         * carries their sum.
         */
        applyColWidths(table, freezeAll = false) {
            const stored = this.$wire.colWidths || {};
            const ths = [
                ...table.querySelectorAll('thead > tr:first-child > th'),
            ];

            if (ths.length === 0) {
                return;
            }

            // Measuring runs on the automatic layout and with no width in the
            // way: a stored width narrower than the column content reads back
            // as the content width there, and that is the one number that must
            // not be frozen.
            table.classList.remove('table-fixed');
            table.classList.add('table-auto');
            table.style.width = '';
            ths.forEach((th) => (th.style.width = ''));

            if (!freezeAll && Object.keys(stored).length === 0) {
                return;
            }

            const widths = ths.map(
                (th) => stored[th.dataset.column] ?? th.offsetWidth,
            );

            ths.forEach((th, i) => (th.style.width = widths[i] + 'px'));
            table.style.width =
                widths.reduce((sum, width) => sum + width, 0) + 'px';
            table.classList.remove('table-auto');
            table.classList.add('table-fixed');
        },

        syncColWidths(table) {
            // Reading both is what subscribes the effect to them. The layout
            // work itself waits a tick, because on the first run the column
            // cells still have to come out of the x-for above them.
            void this.$wire.colWidths;
            void this.$wire.enabledCols;

            this.$nextTick(() => this.applyColWidths(table));
        },

        startResize(event, col) {
            event.preventDefault();
            event.stopPropagation();

            const th = event.target.closest('th');
            const table = th.closest('table');
            const wire = this.$wire;

            // Nothing is pinned before the first drag, so the table is still
            // sized by its content and would ignore a drag to the left.
            this.applyColWidths(table, true);

            const startX = event.clientX;
            const startWidth = th.offsetWidth;
            const startTableWidth = table.offsetWidth;

            const onMouseMove = (e) => {
                const newWidth = Math.max(
                    50,
                    startWidth + (e.clientX - startX),
                );
                th.style.width = newWidth + 'px';
                // The table follows, or the column takes its room from a
                // neighbour instead of from the table's own width.
                table.style.width =
                    startTableWidth + (newWidth - startWidth) + 'px';
            };

            const onMouseUp = () => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                document.body.style.cursor = '';
                document.body.style.userSelect = '';

                // Read back off the cells themselves. Counting past the
                // columns in front of the data went wrong as soon as a search
                // added the relevance column and every width landed one column
                // over.
                const colWidths = {};
                table
                    .querySelectorAll(
                        'thead > tr:first-child > th[data-column]',
                    )
                    .forEach((t) => {
                        if (t.style.width) {
                            colWidths[t.dataset.column] = parseInt(
                                t.style.width,
                                10,
                            );
                        }
                    });

                if (Object.keys(colWidths).length > 0) {
                    wire.colWidths = colWidths;
                    wire.storeColWidths(colWidths);
                }
            };

            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        },

        _setupEchoListeners() {
            if (typeof window.Echo === 'undefined') {
                return;
            }

            const initial = this.$wire.broadcastChannels || {};
            Object.values(initial).forEach((channel) =>
                this._subscribeChannel(channel),
            );

            this._disposers.push(
                this.$watch(
                    '$wire.broadcastChannels',
                    (newChannels, oldChannels) => {
                        const newValues = Object.values(newChannels || {});
                        const oldValues = Object.values(oldChannels || {});

                        oldValues
                            .filter((ch) => !newValues.includes(ch))
                            .forEach((ch) => this._leaveChannel(ch));

                        newValues
                            .filter((ch) => !oldValues.includes(ch))
                            .forEach((ch) => this._subscribeChannel(ch));
                    },
                ),
            );
        },

        _subscribeChannel(channel) {
            if (!channel || this._echoChannels.includes(channel)) {
                return;
            }

            window.Echo.private(channel).listenToAll((event, data) => {
                this.$wire.eloquentEventOccurred(event, data);
            });

            this._echoChannels.push(channel);
        },

        _leaveChannel(channel) {
            window.Echo.leave(channel);
            this._echoChannels = this._echoChannels.filter(
                (ch) => ch !== channel,
            );
        },

        _leaveAllChannels() {
            if (typeof window.Echo === 'undefined') {
                return;
            }

            this._echoChannels.forEach((channel) => window.Echo.leave(channel));
            this._echoChannels = [];
        },
    };
}
