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
                <div class="flex flex-col gap-3">

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
                    <div class="flex gap-3 w-full">
                        <div>
                            <x-radio :label="__('Same time')" value="" x-model="newFilterCalculation.is_start_of" />
                            <x-radio :label="__('Start of')" value="1" x-model="newFilterCalculation.is_start_of" />
                            <x-radio :label="__('End of')" value="0" x-model="newFilterCalculation.is_start_of" />
                        </div>
                        <div class="flex-1" x-show="newFilterCalculation.is_start_of?.length > 0">
                            <x-native-select
                                x-model="newFilterCalculation.start_of"
                                option-key-value="true"
                                :options="[
                                        'minute' => __('Minute'),
                                        'hour' => __('Hour'),
                                        'day' => __('Day'),
                                        'week' => __('Week'),
                                        'month' => __('Month'),
                                        'year' => __('Year')
                                    ]">
                            </x-native-select>
                        </div>
                    </div>
                </div>
            </x-dialog>
        @endif
        {{ $slot }}
    </div>
</div>
