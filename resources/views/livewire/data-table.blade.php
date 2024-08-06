<x-tall-datatables::data-table-wrapper :attributes="$componentAttributes" >
    @includeWhen($includeBefore, $includeBefore)
    @if($hasSidebar)
        <x-tall-datatables::sidebar x-on:keydown.esc="showSidebar = false" x-show="showSidebar">
            <livewire:tall-datatables::options
                :is-filterable="$isFilterable"
                :aggregatable="$aggregatable"
                :is-exportable="$isExportable"
                lazy
            />
            <x-slot:footer>
                <x-button x-on:click="showSidebar = false">{{ __('Close') }}</x-button>
            </x-slot:footer>
        </x-tall-datatables::sidebar>
    @endif
    @if($hasHead)
        <x-tall-datatables::head
            :is-searchable="$searchable"
            :model-name="$modelName"
            :table-actions="$tableActions"
            :headline="$headline"
            :allow-soft-deletes="$allowSoftDeletes"
        />
        @if($actions ?? false)
            <x-dropdown>
                {{ $actions }}
            </x-dropdown>
        @endif
    @endif
    <x-dynamic-component
        :component="$layout"
        :has-head="$hasHead"
        :is-filterable="$isFilterable"
        :show-filter-inputs="$showFilterInputs"
        :table-head-col-attributes="$tableHeadColAttributes"
        :select-attributes="$selectAttributes"
        :selected-actions="$selectedActions"
        :row-actions="$rowActions"
        :row-attributes="$rowAttributes"
        :cell-attributes="$cellAttributes"
        :has-infinite-scroll="$hasInfiniteScroll"
        :has-sticky-cols="$hasStickyCols"
        :has-sidebar="$hasSidebar"
        :use-wire-navigate="$useWireNavigate"
        :is-selectable="$isSelectable"
        :select-value="$selectValue"
        :allow-soft-deletes="$allowSoftDeletes"
        :show-restore-button="$showRestoreButton"
    />
</x-tall-datatables::data-table-wrapper>
