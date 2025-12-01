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

<tr
    x-bind:data-id="record.id"
    x-bind:key="record.id"
    x-on:click="$dispatch('data-table-row-clicked', {record: record})"
    @if($allowSoftDeletes) x-bind:class="record.deleted_at ? 'opacity-50' : ''" @endif
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
                    x-on:click="$event.stopPropagation();"
                    x-on:change="$dispatch('data-table-record-selected', {record: record, index: index, value: $el.checked});"
                    x-bind:value="{{ $selectValue }}"
                    x-model.number="$wire.selected"
                />
            </div>
        </td>
    @else
        <td
            class="max-w-0 border-b border-slate-200 text-sm whitespace-nowrap dark:border-slate-600"
        ></td>
    @endif
    <template x-for="col in enabledCols">
        <x-tall-datatables::table.cell
            :use-wire-navigate="$useWireNavigate"
            x-bind:class="stickyCols.includes(col) && 'sticky left-0 border-r bg-white dark:bg-secondary-800 dark:text-gray-50'"
            x-bind:style="stickyCols.includes(col) && 'z-index: 2'"
            class="cursor-pointer"
            x-bind:href="record.deleted_at ? false : (record?.href ?? false)"
        >
            <div class="flex flex-wrap gap-1.5">
                <div
                    class="flex flex-wrap gap-1"
                    x-cloak
                    x-show="leftAppend[col]"
                    x-html="formatter(leftAppend[col], record)"
                ></div>
                <div class="grow">
                    <div
                        class="flex flex-wrap gap-1"
                        x-cloak
                        x-show="topAppend[col]"
                        x-html="formatter(topAppend[col], record)"
                    ></div>
                    <div
                        class="flex flex-wrap gap-1"
                        {{ $cellAttributes->merge(['x-html' => 'formatter(col, record)']) }}
                    ></div>
                    <div
                        class="flex flex-wrap gap-1"
                        x-cloak
                        x-show="bottomAppend[col]"
                        x-html="formatter(bottomAppend[col], record)"
                    ></div>
                </div>
                <div
                    class="flex flex-wrap gap-1"
                    x-cloak
                    x-show="rightAppend[col]"
                    x-html="formatter(rightAppend[col], record)"
                ></div>
            </div>
        </x-tall-datatables::table.cell>
    </template>
    @if (($rowActions ?? false) || ($showRestoreButton && $allowSoftDeletes))
        <td
            x-on:click.stop
            class="border-b border-slate-200 px-3 py-4 whitespace-nowrap dark:border-slate-600"
        >
            <div
                class="flex gap-1.5"
                @if($allowSoftDeletes) x-bind:class="record.deleted_at ? 'hidden' : ''" @endif
            >
                @foreach ($rowActions ?? [] as $rowAction)
                    {{ $rowAction }}
                @endforeach
            </div>
            @if ($showRestoreButton && $allowSoftDeletes)
                <div class="flex gap-1.5" x-show="record.deleted_at">
                    <x-button
                        color="indigo"
                        wire:click="restore(record.id)"
                    >
                        {{ __('Restore') }}
                    </x-button>
                </div>
            @endif
        </td>
    @endif

    @if ($hasSidebar)
        <td
            class="sticky right-0 table-cell border-b border-slate-200 px-3 py-4 text-sm whitespace-nowrap shadow-sm dark:border-slate-600"
        ></td>
    @endif
</tr>
