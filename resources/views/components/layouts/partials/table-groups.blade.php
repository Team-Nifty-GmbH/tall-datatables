@props([
    'isSelectable' => false,
    'selectValue' => 'record.id',
    'selectAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'rowAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'cellAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'allowSoftDeletes' => false,
    'useWireNavigate' => true,
    'rowActions' => [],
    'showRestoreButton' => false,
    'hasSidebar' => true,
])
@php
    $groups = $this->data['groups'] ?? [];
    $enabledCols = $this->enabledCols;
    $modelKeyName = $this->modelKeyName;
@endphp
@foreach ($groups as $group)
    {{-- Group header row --}}
    <tr
        wire:key="group-header-{{ $group['key'] }}"
        class="dark:bg-secondary-700 cursor-pointer bg-gray-100 hover:bg-gray-200 dark:hover:bg-secondary-600"
        wire:click="toggleGroup('{{ $group['key'] }}')"
    >
        <td colspan="100%" class="border-b border-slate-200 px-3 py-3 dark:border-slate-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icon
                        name="chevron-right"
                        class="h-4 w-4 transition-transform {{ $group['expanded'] ? 'rotate-90' : '' }}"
                    />
                    <span class="font-semibold text-gray-700 dark:text-gray-200">
                        {{ $group['label'] }}
                    </span>
                    <x-badge flat color="gray" :text="(string) $group['count']" />
                </div>
                @if (! empty($group['aggregates']))
                    <div class="flex gap-3 text-sm text-gray-500 dark:text-gray-400">
                        @foreach ($group['aggregates'] as $type => $values)
                            @foreach ($values as $col => $value)
                                <span>{{ \Illuminate\Support\Str::headline($type) }}: {{ $value }}</span>
                            @endforeach
                        @endforeach
                    </div>
                @endif
            </div>
        </td>
    </tr>

    {{-- Group data rows (when expanded) --}}
    @if ($group['expanded'] && ! empty($group['data']))
        @foreach ($group['data'] as $index => $record)
            <tr
                wire:key="group-row-{{ $group['key'] }}-{{ $record[$modelKeyName] ?? $index }}"
                x-on:click="$dispatch('data-table-row-clicked', {record: {{ json_encode($record) }}})"
                @if($allowSoftDeletes && ($record['deleted_at'] ?? null)) class="opacity-50" @endif
                {{ $rowAttributes->merge(['class' => 'hover:bg-gray-100 dark:hover:bg-secondary-900']) }}
            >
                @if ($isSelectable)
                    <td
                        class="border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap dark:border-slate-600"
                    >
                        <div
                            {{ $selectAttributes->merge(['class' => 'flex justify-center']) }}
                        >
                            <input
                                type="checkbox"
                                x-on:click.stop
                                value="{{ $record[$modelKeyName] ?? $index }}"
                                wire:model.number="selected"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800"
                            />
                        </div>
                    </td>
                @else
                    <td
                        class="max-w-0 border-b border-slate-200 text-sm whitespace-nowrap dark:border-slate-600"
                    ></td>
                @endif
                @foreach ($enabledCols as $col)
                    <x-tall-datatables::table.cell
                        :use-wire-navigate="$useWireNavigate"
                        :class="in_array($col, $this->stickyCols) ? 'sticky left-0 border-r bg-white dark:bg-secondary-800 dark:text-gray-50' : ''"
                        :style="in_array($col, $this->stickyCols) ? 'z-index: 2' : ''"
                        :href="(($allowSoftDeletes && ($record['deleted_at'] ?? null)) ? null : ($record['href'] ?? null))"
                    >
                        @if (is_array($record[$col] ?? null) && isset($record[$col]['display']))
                            {!! $record[$col]['display'] !!}
                        @elseif (is_array($record[$col] ?? null) && isset($record[$col]['raw']))
                            {{ $record[$col]['raw'] }}
                        @else
                            {{ $record[$col] ?? '' }}
                        @endif
                    </x-tall-datatables::table.cell>
                @endforeach
                @if ($rowActions || ($showRestoreButton && $allowSoftDeletes))
                    <td
                        x-on:click.stop
                        class="border-b border-slate-200 px-3 py-4 whitespace-nowrap dark:border-slate-600"
                    >
                        @if (! ($allowSoftDeletes && ($record['deleted_at'] ?? null)))
                            <div class="flex gap-1.5">
                                @foreach ($rowActions as $rowAction)
                                    {{ $rowAction }}
                                @endforeach
                            </div>
                        @endif
                        @if ($showRestoreButton && $allowSoftDeletes && ($record['deleted_at'] ?? null))
                            <div class="flex gap-1.5">
                                <x-button
                                    color="indigo"
                                    :text="__('Restore')"
                                    wire:click="restore({{ $record[$modelKeyName] ?? 0 }})"
                                />
                            </div>
                        @endif
                    </td>
                @endif

                @if ($hasSidebar)
                    <td
                        class="table-cell border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap dark:border-slate-600"
                    ></td>
                @endif
            </tr>
        @endforeach

        {{-- Group pagination --}}
        @if ($group['pagination'] && $group['pagination']['last_page'] > 1)
            <tr wire:key="group-pagination-{{ $group['key'] }}">
                <td colspan="100%" class="border-b border-slate-200 px-3 py-2 dark:border-slate-600">
                    <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                        <span>
                            {{ __('Showing') }} {{ $group['pagination']['from'] ?? 0 }}
                            {{ __('to') }} {{ $group['pagination']['to'] ?? 0 }}
                            {{ __('of') }} {{ $group['pagination']['total'] ?? 0 }}
                        </span>
                        <div class="flex gap-1">
                            <x-button
                                color="secondary"
                                light
                                sm
                                icon="chevron-left"
                                :disabled="$group['pagination']['current_page'] <= 1"
                                wire:click="setGroupPage('{{ $group['key'] }}', {{ $group['pagination']['current_page'] - 1 }})"
                            />
                            <x-button
                                color="secondary"
                                light
                                sm
                                icon="chevron-right"
                                :disabled="$group['pagination']['current_page'] >= $group['pagination']['last_page']"
                                wire:click="setGroupPage('{{ $group['key'] }}', {{ $group['pagination']['current_page'] + 1 }})"
                            />
                        </div>
                    </div>
                </td>
            </tr>
        @endif
    @endif
@endforeach

{{-- Groups-level pagination --}}
@if (($this->data['groups_pagination']['last_page'] ?? 1) > 1)
    <tr wire:key="groups-pagination">
        <td colspan="100%" class="border-b border-slate-200 px-3 py-2 dark:border-slate-600">
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    {{ __('Groups') }}: {{ __('Showing') }}
                    {{ $this->data['groups_pagination']['current_page'] ?? 1 }}
                    {{ __('of') }} {{ $this->data['groups_pagination']['last_page'] ?? 1 }}
                </span>
                <div class="flex gap-1">
                    <x-button
                        color="secondary"
                        light
                        sm
                        icon="chevron-left"
                        :disabled="($this->data['groups_pagination']['current_page'] ?? 1) <= 1"
                        wire:click="setGroupsPage({{ ($this->data['groups_pagination']['current_page'] ?? 1) - 1 }})"
                    />
                    <x-button
                        color="secondary"
                        light
                        sm
                        icon="chevron-right"
                        :disabled="($this->data['groups_pagination']['current_page'] ?? 1) >= ($this->data['groups_pagination']['last_page'] ?? 1)"
                        wire:click="setGroupsPage({{ ($this->data['groups_pagination']['current_page'] ?? 1) + 1 }})"
                    />
                </div>
            </div>
        </td>
    </tr>
@endif
