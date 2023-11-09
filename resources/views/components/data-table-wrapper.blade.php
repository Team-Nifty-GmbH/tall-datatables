<div
    wire:init="loadData()"
    x-data
    x-id="['save-filter', 'enabledCols', 'operators', 'filter-select-search', 'table-cols']"
>
    <div
        class="relative"
        wire:ignore
        tall-datatable
        x-data="data_table($wire)"
        {{ $attributes }}
    >
        @if(auth()->user() && method_exists(auth()->user(), 'datatableUserSettings'))
            <x-dialog z-index="z-40" id="save-filter" :title="__('Save filter')">
                <x-input required :label="__('Filter name')" x-model="filterName" />
                <div class="pt-3">
                    <x-checkbox :label="__('Permanent')" x-model="permanent" />
                </div>
            </x-dialog>
        @endif
        @if($this->isFilterable)
            <x-dialog z-index="z-40" id="date-calculation">
                <div class="flex gap-3">
                    <x-button x-bind:class="newFilterCalculation.operator === '-' && 'ring-2 ring-offset-2'" x-on:click="newFilterCalculation.operator = '-'" negative>-</x-button>
                    <x-button x-bind:class="newFilterCalculation.operator === '+' && 'ring-2 ring-offset-2'" x-on:click="newFilterCalculation.operator = '+'" positive>+</x-button>
                    <x-inputs.number min="0" x-model="newFilterCalculation.value" />
                    <x-native-select
                        x-model="newFilterCalculation.unit"
                        option-key-value="true"
                        :options="[
                            'minutes' => __('Minutes'),
                            'hours' => __('Hours'),
                            'days' => __('Days'),
                            'weeks' => __('Weeks'),
                            'months' => __('Months'),
                            'years' => __('Years')
                        ]">
                    </x-native-select>
                </div>
            </x-dialog>
        @endif
        {{ $slot }}
    </div>
</div>
