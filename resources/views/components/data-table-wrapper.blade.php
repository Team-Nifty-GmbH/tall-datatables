<div
    wire:init="loadData"
    x-data="{
        stickyCols: $wire.stickyCols,
        showSelectedActions: false,
    }"
    class="relative"
    tall-datatable
    {{ $attributes }}
>
    {{ $slot }}
</div>
