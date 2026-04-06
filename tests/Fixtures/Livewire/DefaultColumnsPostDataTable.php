<?php

namespace Tests\Fixtures\Livewire;

class DefaultColumnsPostDataTable extends PostDataTable
{
    protected function canSaveDefaultColumns(): bool
    {
        return true;
    }
}
