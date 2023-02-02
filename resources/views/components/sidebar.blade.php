<div class="fixed inset-0 overflow-y-auto p-4 z-30" {{ $attributes->only('x-show') }}>
    <div x-on:click="{{ $attributes->get('x-show') }} = false;" class="fixed inset-0 bg-secondary-400 dark:bg-secondary-700 bg-opacity-60 dark:bg-opacity-60 transform transition-opacity" {{ $attributes }} x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    </div>
    <aside
        x-cloak
        {{ $attributes->merge(['class' => 'fixed right-0 top-0 bottom-0 w-full sm:w-auto h-full backdrop-blur sm:backdrop-blur-none z-30 overflow-auto max-h-full soft-scrollbar shadow-md rounded-xl bg-white dark:bg-secondary-800']) }}
        x-transition:enter="transform transition ease-in-out duration-500"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-500"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
    >
        <div class="px-2 py-5">
            {{ $slot }}
        </div>
        <div class="w-full absolute sticky bottom-0 px-4 py-4 sm:px-6 bg-secondary-50 rounded-t-none border-t dark:bg-secondary-800 dark:border-secondary-600 rounded-xl ">
            <div class="flex justify-end gap-x-4">
                {{ $footer ?? '' }}
            </div>
        </div>
    </aside>
</div>
