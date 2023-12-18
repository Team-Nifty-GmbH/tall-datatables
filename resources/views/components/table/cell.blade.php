@props([
    'useWireNavigate' => false,
])
<a @if($useWireNavigate) x-on:click.prevent="$el.href && Livewire.navigate($el.href)" @endif {{ $attributes->merge(['class' => 'align-top table-cell border-b border-slate-200 dark:border-slate-600 whitespace-normal px-3 py-4 text-sm']) }} >
    {{ $slot ?? '' }}
</a>
