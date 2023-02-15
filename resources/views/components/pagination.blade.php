<div class="flex items-center justify-between px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
        <x-button
            x-bind:disabled="data.current_page === 1"
            x-on:click="$wire.set('page', data.current_page - 1)"
        >{{ __('Previous') }}</x-button>
        <x-button
            x-bind:disabled="data.current_page === data.last_page"
            x-on:click="$wire.set('page', data.current_page + 1)"
        >{{ __('Next') }}</x-button>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-400 flex gap-1">
                {{ __('Showing') }}
                <div x-text="data.from" class="font-medium align-middle"></div>
                {{ __('to') }}
                <div x-text="data.to" class="font-medium"></div>
                {{ __('of') }}
                <div x-text="data.total" class="font-medium"></div>
                {{ __('results') }}
                @if($this->perPage ?? false)
                    <x-select class="pl-4" wire:model="perPage" :clearable="false"
                              option-value="value"
                              option-label="label"
                              :options="[
                                                ['value' => 15, 'label' => '15'],
                                                ['value' => 50, 'label' => '50'],
                                                ['value' => 100, 'label' => '100'],
                                            ]"
                    />
                @endif
            </div>
        </div>
        <div>
            <nav class="isolate inline-flex space-x-1 rounded-md shadow-sm" aria-label="Pagination">
                <x-button
                    x-bind:disabled="data.current_page === 1"
                    x-on:click="$wire.set('page', data.current_page - 1)"
                    icon="chevron-left"
                />
                <template x-for="link in data.links">
                    <x-button
                        x-bind:disabled="link.active"
                        x-html="link.label"
                        x-on:click="$wire.set('page', link.label)"
                        x-bind:class="link.active && 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'"
                    />
                </template>
                <x-button
                    x-bind:disabled="data.current_page === data.last_page"
                    x-on:click="$wire.set('page', data.current_page + 1)"
                    icon="chevron-right"
                />
            </nav>
        </div>
    </div>
</div>
