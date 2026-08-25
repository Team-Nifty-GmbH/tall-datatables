@props([
    'record' => [],
    'index' => 0,
    'isSelectable' => false,
    'hasRelevance' => false,
    'selectValue' => 'record.id',
    'selectAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'rowAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'cellAttributes' => new \Illuminate\View\ComponentAttributeBag(),
    'allowSoftDeletes' => false,
    'useWireNavigate' => true,
    'rowActions' => [],
    'showRestoreButton' => false,
    'hasSidebar' => true,
    'isSortable' => false,
    'leftAppend' => [],
    'rightAppend' => [],
    'topAppend' => [],
    'bottomAppend' => [],
])
@php
    $modelKeyName = $this->modelKeyName;
    $enabledCols = $this->enabledCols;
@endphp
<tr
    wire:key="row-{{ $record[$modelKeyName] ?? $index }}"
    x-data="{ record: {{ json_encode($record) }} }"
    x-on:click="$dispatch('data-table-row-clicked', {record})"
    @if($allowSoftDeletes && ($record['deleted_at'] ?? null)) class="opacity-50" @endif
    @if($isSortable && isset($record[$modelKeyName])) x-sort:item="{{ $record[$modelKeyName] }}" @endif
    {{ $rowAttributes->merge(['class' => 'group hover:bg-gray-50 dark:hover:bg-secondary-900/50 transition-colors']) }}
>
    @if ($isSelectable)
        @php
            $recordKey = json_encode($record[$modelKeyName] ?? $index);
        @endphp
        <td
            class="border-b border-gray-100 px-3 py-2.5 text-sm whitespace-nowrap dark:border-secondary-700/50/50"
        >
            <div
                {{ $selectAttributes->merge(['class' => 'flex items-center justify-center gap-1']) }}
            >
                {{-- the tallstackui checkbox renders four views per row for a
                     label, a position and an error slot this table never uses,
                     so the input is emitted directly. RowCheckboxMarkupTest
                     pins the classes against the component. --}}
                <input
                    type="checkbox"
                    class="form-checkbox dark:border-dark-600 border-1 dark:bg-dark-800 rounded border-gray-300 bg-white ring-0 ring-offset-0 focus:ring-0 focus:ring-offset-0 h-4 w-4 text-primary-500 focus:ring-primary-500 dark:ring-offset-dark-900"
                    x-on:click.stop="$wire.toggleSelected({{ $recordKey }})"
                    x-bind:checked="$wire.selected.includes('*') ? !$wire.wildcardSelectExcluded.includes({{ $recordKey }}) : $wire.selected.includes({{ $recordKey }})"
                />
            </div>
        </td>
    @else
        <td
            class="max-w-0 border-b border-gray-100 px-1 text-sm whitespace-nowrap dark:border-secondary-700/50"
        ></td>
    @endif
    @if ($hasRelevance)
        <td
            class="border-b border-gray-100 px-2 py-2.5 text-sm whitespace-nowrap dark:border-secondary-700/50"
        >
            @isset($record['_relevance'])
                <x-badge flat light color="emerald" :text="$record['_relevance'] . '%'" sm />
            @endisset
        </td>
    @endif
    @foreach ($enabledCols as $col)
        @php
            $cellAttrClasses = trim(
                $cellAttributes->get('class', '') . ' '
                . (preg_match("/^'([^']*)'$/", $cellAttributes->get('x-bind:class', ''), $m) ? $m[1] : '')
            );
        @endphp
        {{-- bound through Alpine like the head and filter cells, so toggling a sticky column takes effect without a re-render --}}
        @php
            $cellHref = ($allowSoftDeletes && ($record['deleted_at'] ?? null)) ? null : ($record['href'] ?? null);
            $cellClasses = \Illuminate\Support\Arr::toCssClasses([
                'border-b border-gray-100 dark:border-secondary-700/50 text-sm p-0',
                'whitespace-nowrap max-w-xs overflow-hidden text-ellipsis' => ! str_contains($cellAttrClasses, 'whitespace-'),
                $cellAttrClasses => $cellAttrClasses !== '',
            ]);
            $cellSticky = '($wire.stickyCols || []).includes(' . \Illuminate\Support\Js::from($col) . ')';
        @endphp
        <td
            class="{{ $cellClasses }}"
            data-column="{{ $col }}"
            x-bind:class="{!! $cellSticky !!} ? 'sticky left-0 border-r bg-white dark:bg-secondary-800 dark:text-gray-50' : ''"
            x-bind:style="{!! $cellSticky !!} ? 'z-index: 2' : ''"
        >
            {{-- always an anchor: without an href it is inert, and pairing the
                 tag here keeps the closing tag in the same branch as the opening one --}}
            <a
                @if ($cellHref) href="{{ $cellHref }}" @endif
                @if ($cellHref && $useWireNavigate) x-on:click.prevent="$el.href && Livewire.navigate($el.href)" @endif
                class="block px-3 py-2.5"
            >
            @php
                $hasLeftAppend = isset($leftAppend[$col]);
                $hasRightAppend = isset($rightAppend[$col]);
                $hasTopAppend = isset($topAppend[$col]);
                $hasBottomAppend = isset($bottomAppend[$col]);
            @endphp
            @if ($hasTopAppend)
                @foreach (\Illuminate\Support\Arr::wrap($topAppend[$col]) as $appendKey)
                    @if (is_array($record[$appendKey] ?? null) && isset($record[$appendKey]['display']))
                        <div>{!! $record[$appendKey]['display'] !!}</div>
                    @elseif (is_string($record[$appendKey] ?? null))
                        <div>{!! $record[$appendKey] !!}</div>
                    @endif
                @endforeach
            @endif
            @if ($hasLeftAppend || $hasRightAppend)
                <div class="flex items-center gap-2">
                    @foreach (\Illuminate\Support\Arr::wrap($leftAppend[$col] ?? []) as $appendKey)
                        @if (is_array($record[$appendKey] ?? null) && isset($record[$appendKey]['display']))
                            {!! $record[$appendKey]['display'] !!}
                        @elseif (is_string($record[$appendKey] ?? null))
                            {!! $record[$appendKey] !!}
                        @endif
                    @endforeach
            @endif
            @if (is_array($record[$col] ?? null) && isset($record[$col]['display']))
                {!! $record[$col]['display'] !!}
            @elseif (is_array($record[$col] ?? null) && isset($record[$col]['raw']))
                {{ $record[$col]['raw'] }}
            @else
                {{ $record[$col] ?? '' }}
            @endif
            @if ($hasLeftAppend || $hasRightAppend)
                    @foreach (\Illuminate\Support\Arr::wrap($rightAppend[$col] ?? []) as $appendKey)
                        @if (is_array($record[$appendKey] ?? null) && isset($record[$appendKey]['display']))
                            {!! $record[$appendKey]['display'] !!}
                        @elseif (is_string($record[$appendKey] ?? null))
                            {!! $record[$appendKey] !!}
                        @endif
                    @endforeach
                </div>
            @endif
            @if ($hasBottomAppend)
                @foreach (\Illuminate\Support\Arr::wrap($bottomAppend[$col]) as $appendKey)
                    @if (is_array($record[$appendKey] ?? null) && isset($record[$appendKey]['display']))
                        <div>{!! $record[$appendKey]['display'] !!}</div>
                    @elseif (is_string($record[$appendKey] ?? null))
                        <div>{!! $record[$appendKey] !!}</div>
                    @endif
                @endforeach
            @endif
            </a>
        </td>
    @endforeach
    @if ($rowActions || ($showRestoreButton && $allowSoftDeletes))
        <td
            x-on:click.stop
            class="border-b border-gray-100 px-3 py-2.5 whitespace-nowrap dark:border-secondary-700/50"
        >
            @if (! ($allowSoftDeletes && ($record['deleted_at'] ?? null)))
                <div class="flex gap-1.5 whitespace-nowrap">
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
            class="table-cell border-b border-gray-100 px-3 py-2.5 text-sm whitespace-nowrap dark:border-secondary-700/50"
        ></td>
    @endif
</tr>
