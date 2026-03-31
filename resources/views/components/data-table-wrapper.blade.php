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
    }"
    class="relative"
    tall-datatable
    {{ $attributes }}
>
    {{ $slot }}
</div>
