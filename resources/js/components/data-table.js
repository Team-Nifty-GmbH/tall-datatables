export default function data_table() {
    return {
        stickyCols: [],
        showSelectedActions: false,
        _echoChannels: [],

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

    };
}
