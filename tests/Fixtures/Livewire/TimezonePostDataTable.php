<?php

namespace Tests\Fixtures\Livewire;

class TimezonePostDataTable extends PostDataTable
{
    protected function getDatabaseTimezone(): string
    {
        return 'UTC';
    }

    protected function getDisplayTimezone(): string
    {
        return 'Europe/Berlin';
    }
}
