<div>
    {{-- Active filters --}}
    @if ($filters)
        <div class="mb-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Active Filters') }}
                </span>
                <x-button
                    :text="__('Clear All')"
                    color="red"
                    flat
                    sm
                    wire:click="clearFilters"
                />
            </div>

            @foreach ($filters as $index => $filter)
                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                    <x-badge :text="$filter['column']" color="primary" />
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $filter['operator'] ?? '=' }}</span>
                    <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-300">{{ $filter['value'] ?? '' }}</span>
                    <button
                        type="button"
                        wire:click="removeFilter({{ $index }})"
                        class="text-gray-400 hover:text-red-500 dark:hover:text-red-400"
                    >
                        <x-icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add filter form --}}
    <div
        x-data="{
            column: '',
            operator: 'like',
            value: '',
            addFilter() {
                if (this.column && this.value !== '') {
                    $wire.addFilter({ column: this.column, operator: this.operator, value: this.value });
                    this.column = '';
                    this.operator = 'like';
                    this.value = '';
                }
            }
        }"
        class="space-y-3"
    >
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <x-select.native
                    :label="__('Column')"
                    x-model="column"
                >
                    <option value="">{{ __('Select column…') }}</option>
                    @foreach ($availableCols as $col)
                        <option value="{{ $col }}">{{ $col }}</option>
                    @endforeach
                </x-select.native>
            </div>

            <div>
                <x-select.native
                    :label="__('Operator')"
                    x-model="operator"
                >
                    <option value="like">{{ __('contains') }}</option>
                    <option value="not_like">{{ __('does not contain') }}</option>
                    <option value="=">{{ __('equals') }}</option>
                    <option value="!=">{{ __('not equals') }}</option>
                    <option value=">">{{ __('greater than') }}</option>
                    <option value=">=">{{ __('greater or equal') }}</option>
                    <option value="<">{{ __('less than') }}</option>
                    <option value="<=">{{ __('less or equal') }}</option>
                </x-select.native>
            </div>

            <div>
                <x-input
                    :label="__('Value')"
                    x-model="value"
                    x-on:keydown.enter="addFilter()"
                />
            </div>
        </div>

        <x-button
            :text="__('Add Filter')"
            color="primary"
            icon="plus"
            x-on:click="addFilter()"
        />
    </div>
</div>
