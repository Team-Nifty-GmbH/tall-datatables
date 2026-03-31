<div class="flex items-center justify-between border-t border-gray-100 px-3 py-2.5 dark:border-secondary-700/50">
    <div class="flex flex-1 justify-between sm:hidden">
        <x-button
            color="secondary"
            flat
            sm
            :text="__('Previous')"
            :disabled="($this->data['current_page'] ?? 1) <= 1"
            wire:click="gotoPage({{ ($this->data['current_page'] ?? 1) - 1 }})"
        />
        <x-button
            color="secondary"
            flat
            sm
            :text="__('Next')"
            :disabled="($this->data['current_page'] ?? 1) >= ($this->data['last_page'] ?? 1)"
            wire:click="gotoPage({{ ($this->data['current_page'] ?? 1) + 1 }})"
        />
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Showing') }}
                <span class="font-medium">{{ $this->data['from'] ?? 0 }}</span>
                {{ __('to') }}
                <span class="font-medium">{{ $this->data['to'] ?? 0 }}</span>
                {{ __('of') }}
                <span class="font-medium">{{ $this->data['total'] ?? 0 }}</span>
                {{ __('results') }}
                @if ($this->perPage ?? false)
                    <x-select.native
                        class="ml-1 border-0 bg-transparent py-0 pr-6 pl-1 text-sm text-gray-600 focus:ring-0 dark:text-gray-300"
                        wire:model.live="perPage"
                        x-on:change="$wire.setPerPage($event.target.value)"
                    >
                        <option value="15">15 {{ __('per page') }}</option>
                        <option value="25">25 {{ __('per page') }}</option>
                        <option value="50">50 {{ __('per page') }}</option>
                        <option value="100">100 {{ __('per page') }}</option>
                    </x-select.native>
                @endif
            </div>
        </div>
        <div>
            <nav
                class="isolate inline-flex space-x-1 rounded-md"
                aria-label="Pagination"
            >
                <x-button
                    color="secondary"
                    flat
                    sm
                    :disabled="($this->data['current_page'] ?? 1) <= 1"
                    wire:click="gotoPage({{ ($this->data['current_page'] ?? 1) - 1 }})"
                    icon="chevron-left"
                />
                @foreach ($this->data['links'] ?? [] as $link)
                    <x-button
                        color="secondary"
                        flat
                        sm
                        :disabled="$link['active'] || $link['url'] === null"
                        :text="$link['label']"
                        wire:click="{{ $link['url'] !== null && !$link['active'] ? 'gotoPage(' . $link['label'] . ')' : '' }}"
                        :class="$link['active'] ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400' : ''"
                    />
                @endforeach
                <x-button
                    color="secondary"
                    flat
                    sm
                    :disabled="($this->data['current_page'] ?? 1) >= ($this->data['last_page'] ?? 1)"
                    wire:click="gotoPage({{ ($this->data['current_page'] ?? 1) + 1 }})"
                    icon="chevron-right"
                />
            </nav>
        </div>
    </div>
</div>
