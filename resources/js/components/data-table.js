export default function data_table() {
    return {
        stickyCols: [],
        showSelectedActions: false,
        _echoChannels: [],
        _resizing: null,

        init() {
            this.stickyCols = this.$wire.stickyCols || [];
            this._setupEchoListeners();
        },

        destroy() {
            this._leaveAllChannels();
        },

        _setupEchoListeners() {
            if (typeof window.Echo === 'undefined') {
                return;
            }

            const initialChannels = this.$wire.broadcastChannels || {};
            Object.values(initialChannels).forEach((channel) => {
                this._subscribeChannel(channel);
            });

            this.$watch('$wire.broadcastChannels', (newChannels, oldChannels) => {
                const newValues = Object.values(newChannels || {});
                const oldValues = Object.values(oldChannels || {});

                oldValues
                    .filter((ch) => !newValues.includes(ch))
                    .forEach((ch) => this._leaveChannel(ch));

                newValues
                    .filter((ch) => !oldValues.includes(ch))
                    .forEach((ch) => this._subscribeChannel(ch));
            });
        },

        _subscribeChannel(channel) {
            if (this._echoChannels.includes(channel)) {
                return;
            }

            Echo.private(channel).listenToAll((event, data) => {
                this.$wire.eloquentEventOccurred(event, data);
            });

            this._echoChannels.push(channel);
        },

        _leaveChannel(channel) {
            Echo.leave(channel);
            this._echoChannels = this._echoChannels.filter(
                (ch) => ch !== channel,
            );
        },

        _leaveAllChannels() {
            if (typeof window.Echo === 'undefined') {
                return;
            }

            this._echoChannels.forEach((channel) => Echo.leave(channel));
            this._echoChannels = [];
        },

        toggleStickyCol(col) {
            let cols = [...this.stickyCols];
            let index = cols.indexOf(col);
            if (index > -1) {
                cols.splice(index, 1);
            } else {
                cols.push(col);
            }
            this.stickyCols = cols;
            this.$wire.stickyCols = cols;
        },

        startResize(event, col) {
            event.preventDefault();
            event.stopPropagation();

            let th = event.target.closest('th');
            let startX = event.clientX;
            let startWidth = th.offsetWidth;
            let table = th.closest('table');

            table.classList.remove('table-auto');
            table.classList.add('table-fixed');

            let onMouseMove = (e) => {
                let newWidth = Math.max(50, startWidth + (e.clientX - startX));
                th.style.width = newWidth + 'px';
            };

            let onMouseUp = () => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                document.body.style.cursor = '';
                document.body.style.userSelect = '';

                this._persistColWidths(table);
            };

            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        },

        _persistColWidths(table) {
            let colWidths = {};
            let cols = this.$wire.enabledCols || [];
            let ths = table.querySelectorAll('thead > tr:first-child > th');

            // Skip first th (checkbox/spacer column)
            let offset = 1;
            cols.forEach((col, i) => {
                let th = ths[i + offset];
                if (th && th.style.width) {
                    colWidths[col] = parseInt(th.style.width, 10);
                }
            });

            if (Object.keys(colWidths).length > 0) {
                this.$wire.colWidths = colWidths;
                this.$wire.storeColWidths(colWidths);
            }
        },
    };
}
