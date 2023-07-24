{{ $title ?? '' }}
<div {{ $attributes->merge(['class' => 'mt-3 flex flex-col'])->except('wire:sortable') }}>
    <div class="relative overflow-x-auto shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
        <table class="dark:divide-secondary-700 dark:bg-secondary-800 min-w-full table-auto border-collapse divide-y
            divide-gray-300 bg-white text-gray-500 dark:text-gray-50 rounded-md">
            <thead class="font-semibold uppercase" style="z-index: 9;">
                {{ $header ?? '' }}
            </thead>
            <tbody class="relative" {{ $attributes->thatStartWith('wire:sortable') }}>
                {{ $slot ?? '' }}
            </tbody>
            <tfoot>
                {{ $footer ?? '' }}
            </tfoot>
        </table>
    </div>
</div>
