@props([
    'useWireNavigate' => false,
    'href' => null,
])
<td
    {{ $attributes->only(['class', 'x-bind:class', 'x-bind:style'])->merge(['class' => 'align-top border-b border-slate-200 dark:border-slate-600 whitespace-nowrap max-w-xs overflow-hidden text-ellipsis px-3 py-4 text-sm']) }}
>
    @if($attributes->get('x-bind:href') || $href)
        <a
            @if($useWireNavigate) x-on:click.prevent="$el.href && Livewire.navigate($el.href)" @endif
            {{ $attributes->only(['x-bind:href', 'href']) }}
            class="block"
        >
            {{ $slot ?? '' }}
        </a>
    @else
        {{ $slot ?? '' }}
    @endif
</td>
