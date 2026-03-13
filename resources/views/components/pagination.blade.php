<div class="flex items-center justify-between px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
        <x-button
            color="secondary"
            light
            :text="__('Previous')"
            :disabled="($this->data['current_page'] ?? 1) <= 1"
            wire:click="gotoPage({{ ($this->data['current_page'] ?? 1) - 1 }})"
        />
        <x-button
            color="secondary"
            light
            :text="__('Next')"
            :disabled="($this->data['current_page'] ?? 1) >= ($this->data['last_page'] ?? 1)"
            wire:click="gotoPage({{ ($this->data['current_page'] ?? 1) + 1 }})"
        />
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-1 text-sm text-slate-400">
                {{ __('Showing') }}
                <div class="align-middle font-medium">{{ $this->data['from'] ?? 0 }}</div>
                {{ __('to') }}
                <div class="font-medium">{{ $this->data['to'] ?? 0 }}</div>
                {{ __('of') }}
                <div class="font-medium">{{ $this->data['total'] ?? 0 }}</div>
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
                    :disabled="($this->data['current_page'] ?? 1) <= 1"
                    wire:click="gotoPage({{ ($this->data['current_page'] ?? 1) - 1 }})"
                    icon="chevron-left"
                />
                @foreach ($this->data['links'] ?? [] as $link)
                    <x-button
                        color="secondary"
                        light
                        :disabled="$link['active'] || $link['url'] === null"
                        :text="$link['label']"
                        wire:click="{{ $link['url'] !== null && !$link['active'] ? 'gotoPage(' . $link['label'] . ')' : '' }}"
                        :class="$link['active'] ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : ''"
                    />
                @endforeach
                <x-button
                    color="secondary"
                    light
                    :disabled="($this->data['current_page'] ?? 1) >= ($this->data['last_page'] ?? 1)"
                    wire:click="gotoPage({{ ($this->data['current_page'] ?? 1) + 1 }})"
                    icon="chevron-right"
                />
            </nav>
        </div>
    </div>
</div>
