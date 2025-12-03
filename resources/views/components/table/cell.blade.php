@props([
    'useWireNavigate' => false,
    'href' => null,
])
@php
    $hasStaticHref = $href !== null;
    $hasDynamicHref = $attributes->has('x-bind:href');
    $dynamicHrefExpr = $hasDynamicHref ? $attributes->get('x-bind:href') : 'false';
@endphp
<td
    {{ $attributes->only(['class', 'x-bind:class', 'x-bind:style'])->merge(['class' => 'border-b border-slate-200 dark:border-slate-600 whitespace-nowrap max-w-xs overflow-hidden text-ellipsis text-sm p-0']) }}
>
    @if($hasStaticHref)
        <a
            @if($useWireNavigate) x-on:click.prevent="$el.href && Livewire.navigate($el.href)" @endif
            href="{{ $href }}"
            class="block px-3 py-4"
        >
            {{ $slot ?? '' }}
        </a>
    @elseif($hasDynamicHref)
        <a
            @if($useWireNavigate)
                x-on:click.prevent="$el.href && Livewire.navigate($el.href)"
            @endif
            x-bind:href="{{ $dynamicHrefExpr }} || null"
            x-bind:class="({{ $dynamicHrefExpr }}) && 'cursor-pointer'"
            class="block px-3 py-4"
        >
            {{ $slot ?? '' }}
        </a>
    @else
        <div class="px-3 py-4">
            {{ $slot ?? '' }}
        </div>
    @endif
</td>
