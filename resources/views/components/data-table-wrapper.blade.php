<div
    x-data="{
        get stickyCols() { return $wire.stickyCols; },
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

                let colWidths = {};
                let cols = $wire.enabledCols || [];
                let ths = table.querySelectorAll('thead > tr:first-child > th');
                let offset = 1;
                cols.forEach((c, i) => {
                    let t = ths[i + offset];
                    if (t && t.style.width) {
                        colWidths[c] = parseInt(t.style.width, 10);
                    }
                });

                if (Object.keys(colWidths).length > 0) {
                    $wire.colWidths = colWidths;
                    $wire.storeColWidths(colWidths);
                }
            };

            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
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
