<div class="space-y-6">
    {{-- Column visibility --}}
    <div>
        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
            {{ __('Columns') }}
        </h3>
        <div class="space-y-2">
            @foreach ($availableCols as $col)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $col }}</span>
                    <x-toggle
                        :label="null"
                        wire:click="toggleColumn('{{ $col }}')"
                        x-bind:checked="{{ in_array($col, $enabledCols) ? 'true' : 'false' }}"
                    />
                </div>
            @endforeach
        </div>
    </div>

    {{-- Aggregation (only when aggregatable columns exist) --}}
    @if ($aggregatable)
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ __('Aggregation') }}
            </h3>
            <div class="space-y-2">
                @foreach ($aggregatable as $col)
                    <div class="flex items-center gap-3">
                        <span class="w-1/3 text-sm text-gray-600 dark:text-gray-400">{{ $col }}</span>
                        <div class="flex-1">
                            <x-select.native
                                wire:change="setAggregation('{{ $col }}', $event.target.value)"
                            >
                                <option value="">{{ __('None') }}</option>
                                <option value="sum" @selected(($aggregations[$col] ?? null) === 'sum')>{{ __('Sum') }}</option>
                                <option value="avg" @selected(($aggregations[$col] ?? null) === 'avg')>{{ __('Average') }}</option>
                                <option value="min" @selected(($aggregations[$col] ?? null) === 'min')>{{ __('Min') }}</option>
                                <option value="max" @selected(($aggregations[$col] ?? null) === 'max')>{{ __('Max') }}</option>
                                <option value="count" @selected(($aggregations[$col] ?? null) === 'count')>{{ __('Count') }}</option>
                            </x-select.native>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Group by --}}
    @if ($availableCols)
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ __('Group By') }}
            </h3>
            <x-select.native
                :label="null"
                wire:change="setGroupBy($event.target.value ?: null)"
            >
                <option value="">{{ __('No grouping') }}</option>
                @foreach ($availableCols as $col)
                    <option value="{{ $col }}" @selected($groupBy === $col)>{{ $col }}</option>
                @endforeach
            </x-select.native>
        </div>
    @endif

    {{-- Export --}}
    @if ($isExportable)
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ __('Export') }}
            </h3>
            <x-button
                :text="__('Export CSV')"
                color="secondary"
                icon="arrow-down-tray"
                wire:click="$dispatch('export-datatable')"
            />
        </div>
    @endif
</div>
