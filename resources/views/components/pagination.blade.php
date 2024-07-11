<div class="flex items-center justify-between px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
        <x-button
            x-bind:disabled="data.current_page === 1"
            wire:click="goToPage(data.current_page - 1)"
        >{{ __('Previous') }}</x-button>
        <x-button
            x-bind:disabled="data.current_page === data.last_page"
            wire:click="goToPage(data.current_page + 1)"
        >{{ __('Next') }}</x-button>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-400 flex gap-1 items-center">
                {{ __('Showing') }}
                <div x-text="data.from" class="font-medium align-middle"></div>
                {{ __('to') }}
                <div x-text="data.to" class="font-medium"></div>
                {{ __('of') }}
                <div x-text="data.total" class="font-medium"></div>
                {{ __('results') }}
                @if($this->perPage ?? false)
                    <x-select class="pl-4" x-on:selected="$wire.setPerPage($event.detail.value)" wire:model="perPage" :clearable="false"
                              option-value="value"
                              option-label="label"
                              :options="[
                                            ['value' => 15, 'label' => '15 ' . __('per page')],
                                            ['value' => 25, 'label' => '25 ' . __('per page')],
                                            ['value' => 50, 'label' => '50 ' . __('per page')],
                                            ['value' => 100, 'label' => '100 ' . __('per page')],
                                        ]"
                    />
                @endif
            </div>
        </div>
        <div>
            <nav class="isolate inline-flex space-x-1 rounded-md shadow-sm" aria-label="Pagination">
                <x-button
                    flat
                    x-bind:disabled="data.current_page === 1"
                    wire:click="goToPage(data.current_page - 1)"
                    icon="chevron-left"
                />
                <template x-for="link in data.links">
                    <x-button
                        flat
                        x-bind:disabled="link.active || link.url === null"
                        x-html="link.label"
                        x-on:click="if (link.url !== null) $wire.goToPage(link.label)"
                        x-bind:class="link.active && 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'"
                    />
                </template>
                <x-button
                    flat
                    x-bind:disabled="data.current_page === data.last_page"
                    wire:click="goToPage(data.current_page + 1)"
                    icon="chevron-right"
                />
            </nav>
        </div>
    </div>
</div>
