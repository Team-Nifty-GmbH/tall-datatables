<div
    x-data="{
        stickyCols: $wire.stickyCols,
        showSelectedActions: false,
        textFilterRows: (() => {
            const tf = $wire.textFilters || {};
            const keys = Object.keys(tf).filter(k => !isNaN(k));
            return keys.length > 0 ? keys.map(Number).sort((a, b) => a - b) : [0];
        })(),
        addTextFilterRow() {
            const next = this.textFilterRows.length > 0
                ? Math.max(...this.textFilterRows) + 1
                : 0;
            this.textFilterRows = [...this.textFilterRows, next];
        },
        removeTextFilterRow(index) {
            this.textFilterRows = this.textFilterRows.filter(i => i !== index);
            if (this.textFilterRows.length === 0) this.textFilterRows = [0];
            $wire.removeTextFilterRow(index);
        },
        extraInputs: {},
        getInputCount(rowIndex, col) {
            const tf = ($wire.textFilters || {})[rowIndex] || {};
            const val = tf[col];
            const serverCount = Array.isArray(val) ? val.length : (val ? 1 : 1);
            const localCount = this.extraInputs[rowIndex + '-' + col] || 0;
            return Math.max(serverCount, 1 + localCount);
        },
        addColumnInput(rowIndex, col) {
            const key = rowIndex + '-' + col;
            this.extraInputs[key] = (this.extraInputs[key] || 0) + 1;
            this.extraInputs = { ...this.extraInputs };
        },
        removeColumnInput(rowIndex, col, valueIndex) {
            $wire.setTextFilter(col, '', rowIndex, valueIndex);
            const key = rowIndex + '-' + col;
            if (this.extraInputs[key] > 0) {
                this.extraInputs[key]--;
                this.extraInputs = { ...this.extraInputs };
            }
        },
        getTextFilterValue(rowIndex, col, valueIndex) {
            const tf = ($wire.textFilters || {})[rowIndex] || {};
            const val = tf[col];
            if (Array.isArray(val)) return val[valueIndex] || '';
            return valueIndex === 0 ? (val || '') : '';
        },
        init() {
            this.$watch(() => JSON.stringify($wire.textFilters), () => {
                const tf = $wire.textFilters || {};
                const keys = Object.keys(tf).filter(k => !isNaN(k));
                const serverRows = keys.length > 0 ? keys.map(Number).sort((a, b) => a - b) : [0];
                if (serverRows.length < this.textFilterRows.length) {
                    this.textFilterRows = serverRows;
                }
            });
        },
    }"
    class="relative"
    tall-datatable
    {{ $attributes }}
>
    {{ $slot }}
</div>
