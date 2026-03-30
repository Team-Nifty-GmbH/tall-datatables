@props([
    'record' => [],
    'index' => 0,
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
    $modelKeyName = $this->modelKeyName;
    $enabledCols = $this->enabledCols;
@endphp
<tr
    wire:key="row-{{ $record[$modelKeyName] ?? $index }}"
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
                <x-checkbox
                    x-on:click.stop
                    value="{{ $record[$modelKeyName] ?? $index }}"
                    wire:model.number="selected"
                    sm
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
