<?php

namespace Tests\Fixtures\Livewire;

class SharedFiltersPostDataTable extends PostDataTable
{
    protected function canShareFilters(): bool
    {
        return true;
    }
}
