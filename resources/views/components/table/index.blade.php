{{ $title ?? '' }}
<div {{ $attributes->merge(['class' => 'mt-3 flex flex-col'])->except('wire:sortable') }}>
    <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="dark:divide-secondary-700 dark:bg-secondary-800 min-w-full table-auto border-collapse divide-y divide-gray-300 bg-white text-gray-500 dark:text-gray-50">
                    <thead class="sticky top-0 font-semibold uppercase">
                        <tr>
                            {{ $header ?? '' }}
                        </tr>
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
    </div>
</div>
