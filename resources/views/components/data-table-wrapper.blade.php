@props(['eloquentEvents' => []])

<div
    x-data="data_table(@js($eloquentEvents))"
    class="relative"
    tall-datatable
    {{ $attributes }}
>
    {{ $slot }}
</div>
