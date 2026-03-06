<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Livewire\Attributes\Renderless;

trait SupportsSorting
{
    #[Renderless]
    public function sortRows(int|string $recordId, int $newPosition): void
    {
        //
    }

    protected function isSortable(): bool
    {
        return false;
    }
}
