<div
    class="mt-2 px-1"
    x-data="datatableOptions($wire)"
    x-init="
        aggregatable = {{ Js::from($this->getAggregatable()) }};
        groupable = {{ Js::from($this->getGroupableCols()) }};
        operatorLabels = {{ Js::from($this->getOperatorLabels()) }};
    "
>
    @if (auth()->user() && method_exists(auth()->user(), 'datatableUserSettings'))
        <x-modal
            persistent
            id="save-filter"
            :title="__('Save filter')"
            x-on:close="filterName = ''; permanent = false; isShared = false;"
            x-on:open="$focusOn('filter-name')"
        >
            <x-input sm
                required
                id="filter-name"
                :label="__('Filter name')"
                x-model="filterName"
            />
            <div class="flex flex-col gap-1.5 pt-3">
                <x-checkbox :label="__('Permanent')" x-model="permanent" />
                <x-checkbox
                    :label="__('With column layout')"
                    x-model="withEnabledCols"
                />
                @if($this->canShareFilters())
                    <x-toggle :label="__('Share with team')" x-model="isShared" />
                @endif
            </div>
            <x-slot:footer>
                <x-button
                    color="secondary"
                    light
                    flat
                    :text="__('Cancel')"
                    x-on:click="$tsui.close.modal('save-filter')"
                />
                <x-button
                    :text="__('Save')"
                    x-on:click="$wire.saveFilter(filterName, permanent, withEnabledCols, isShared).then(() => $tsui.close.modal('save-filter'));"
                />
            </x-slot>
        </x-modal>
    @endif

    <x-tab
        :selected="$this->isFilterable ? 'edit-filters' : 'columns'"
        scroll-on-mobile
        scope="datatable"
        x-on:navigate="handleTabNavigate($event.detail.select)"
    >
        @foreach ($this->getSidebarTabs() as $tab)
            <x-tab.items :tab="$tab['id']" :title="$tab['label']">
                @include($tab['view'])
            </x-tab.items>
        @endforeach
    </x-tab>
</div>
