<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class SortablePostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
    ];

    public array $sortedRows = [];

    protected string $model = Post::class;

    public function sortRows(int|string $recordId, int $newPosition): void
    {
        $this->sortedRows[] = ['id' => $recordId, 'position' => $newPosition];
    }

    protected function isSortable(): bool
    {
        return true;
    }
}
