<x-tall-datatables::data-table-wrapper :attributes="$componentAttributes" >
    @if($hasSidebar)
        <x-tall-datatables::options />
    @endif
    @if($hasHead)
        <x-tall-datatables::head
            :is-searchable="$searchable"
            :model-name="$modelName"
            :table-actions="$tableActions"
            :headline="$headline"
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
    />
</x-tall-datatables::data-table-wrapper>
