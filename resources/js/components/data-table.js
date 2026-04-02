export default function data_table() {
    return {
        stickyCols: [],
        showSelectedActions: false,

        init() {
            this.stickyCols = this.$wire.stickyCols || [];
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
