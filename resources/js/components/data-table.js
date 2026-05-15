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

        startResize(event, col) {
            event.preventDefault();
            event.stopPropagation();

            const th = event.target.closest('th');
            const startX = event.clientX;
            const startWidth = th.offsetWidth;
            const table = th.closest('table');
            const wire = this.$wire;

            table.classList.remove('table-auto');
            table.classList.add('table-fixed');

            const onMouseMove = (e) => {
                const newWidth = Math.max(
                    50,
                    startWidth + (e.clientX - startX),
                );
                th.style.width = newWidth + 'px';
            };

            const onMouseUp = () => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                document.body.style.cursor = '';
                document.body.style.userSelect = '';

                const colWidths = {};
                const cols = wire.enabledCols || [];
                const ths = table.querySelectorAll(
                    'thead > tr:first-child > th',
                );
                const offset = 1;
                cols.forEach((c, i) => {
                    const t = ths[i + offset];
                    if (t && t.style.width) {
                        colWidths[c] = parseInt(t.style.width, 10);
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
                this.$watch('$wire.broadcastChannels', (newChannels, oldChannels) => {
                    const newValues = Object.values(newChannels || {});
                    const oldValues = Object.values(oldChannels || {});

                    oldValues
                        .filter((ch) => !newValues.includes(ch))
                        .forEach((ch) => this._leaveChannel(ch));

                    newValues
                        .filter((ch) => !oldValues.includes(ch))
                        .forEach((ch) => this._subscribeChannel(ch));
                }),
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

            this._echoChannels.forEach((channel) =>
                window.Echo.leave(channel),
            );
            this._echoChannels = [];
        },
    };
}
