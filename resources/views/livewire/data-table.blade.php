<x-tall-datatables::data-table-wrapper :attributes="$componentAttributes" >
    <x-tall-datatables::options />
    @if($hasHead)
        <x-tall-datatables::head :model-name="$modelName" :table-actions="$tableActions" />
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
        :select-attributes="$selectAttributes"
        :row-actions="$rowActions"
        :row-attributes="$rowAttributes"
        :has-infinite-scroll="$hasInfiniteScroll"
    />
</x-tall-datatables::data-table-wrapper>
