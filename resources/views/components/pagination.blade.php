<div class="flex items-center justify-between px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
        <x-button
            color="secondary"
            light
            :text="__('Previous')"
            x-bind:disabled="data.current_page === 1"
            wire:click="goToPage(data.current_page - 1)"
        />
        <x-button
            color="secondary"
            light
            :text="__('Next')"
            x-bind:disabled="data.current_page === data.last_page"
            wire:click="goToPage(data.current_page + 1)"
        />
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-1 text-sm text-slate-400">
                {{ __('Showing') }}
                <div
                    x-text="data.from ?? 0"
                    class="align-middle font-medium"
                ></div>
                {{ __('to') }}
                <div x-text="data.to ?? 0" class="font-medium"></div>
                {{ __('of') }}
                <div x-text="data.total" class="font-medium"></div>
                {{ __('results') }}
                @if ($this->perPage ?? false)
                    <x-select.styled
                        class="pl-4"
                        x-on:select="$wire.setPerPage($event.detail.select.value)"
                        wire:model="perPage"
                        required
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
            <nav
                class="isolate inline-flex space-x-1 rounded-md shadow-sm"
                aria-label="Pagination"
            >
                <x-button
                    color="secondary"
                    light
                    x-bind:disabled="data.current_page === 1"
                    wire:click="goToPage(data.current_page - 1)"
                    icon="chevron-left"
                />
                <template x-for="link in data.links">
                    <x-button
                        color="secondary"
                        light
                        x-bind:disabled="link.active || link.url === null"
                        x-html="link.label"
                        x-on:click="if (link.url !== null) $wire.goToPage(link.label)"
                        x-bind:class="link.active && 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'"
                    />
                </template>
                <x-button
                    color="secondary"
                    light
                    x-bind:disabled="data.current_page === data.last_page"
                    wire:click="goToPage(data.current_page + 1)"
                    icon="chevron-right"
                />
            </nav>
        </div>
    </div>
</div>
