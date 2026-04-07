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

    @if ($this->isFilterable)
        <x-modal persistent id="date-calculation">
            <div class="flex flex-col gap-3">
                <div class="flex gap-3">
                    <x-button
                        x-bind:class="newFilterCalculation.operator === '-' && 'ring-2 ring-offset-2'"
                        x-on:click="newFilterCalculation.operator = '-'"
                        color="red"
                        text="-"
                    />
                    <x-button
                        x-bind:class="newFilterCalculation.operator === '+' && 'ring-2 ring-offset-2'"
                        x-on:click="newFilterCalculation.operator = '+'"
                        color="emerald"
                        text="+"
                    />
                    <x-number min="0" x-model="newFilterCalculation.value" />
                    <x-select.styled
                        x-model="newFilterCalculation.unit"
                        :options="[
                            [
                                'label' => __('Minutes'),
                                'value' => 'minutes',
                            ],
                            [
                                'label' => __('Hours'),
                                'value' => 'hours',
                            ],
                            [
                                'label' => __('Days'),
                                'value' => 'days',
                            ],
                            [
                                'label' => __('Weeks'),
                                'value' => 'weeks',
                            ],
                            [
                                'label' => __('Months'),
                                'value' => 'months',
                            ],
                            [
                                'label' => __('Years'),
                                'value' => 'years',
                            ]
                        ]"
                    />
                </div>
                <div class="flex w-full gap-3">
                    <div>
                        <x-radio
                            :label="__('Same time')"
                            value=""
                            x-model="newFilterCalculation.is_start_of"
                        />
                        <x-radio
                            :label="__('Start of')"
                            value="1"
                            x-model="newFilterCalculation.is_start_of"
                        />
                        <x-radio
                            :label="__('End of')"
                            value="0"
                            x-model="newFilterCalculation.is_start_of"
                        />
                    </div>
                    <div
                        class="flex-1"
                        x-cloak
                        x-show="newFilterCalculation.is_start_of?.length > 0"
                    >
                        <x-select.styled
                            x-model="newFilterCalculation.start_of"
                            :options="[
                                [
                                    'label' => __('Minute'),
                                    'value' => 'minute',
                                ],
                                [
                                    'label' => __('Hour'),
                                    'value' => 'hour',
                                ],
                                [
                                    'label' => __('Day'),
                                    'value' => 'day',
                                ],
                                [
                                    'label' => __('Week'),
                                    'value' => 'week',
                                ],
                                [
                                    'label' => __('Month'),
                                    'value' => 'month',
                                ],
                                [
                                    'label' => __('Year'),
                                    'value' => 'year',
                                ],
                            ]"
                        />
                    </div>
                </div>
            </div>
            <x-slot:footer>
                <x-button
                    color="secondary"
                    light
                    flat
                    :text="__('Cancel')"
                    x-on:click="$tsui.close.modal('date-calculation')"
                />
                <x-button
                    :text="__('Save')"
                    x-on:click="addCalculation(dateCalculation); $tsui.close.modal('date-calculation');"
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
