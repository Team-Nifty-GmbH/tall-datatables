<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class SelectablePostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
    ];

    public bool $isSelectable = true;

    protected string $model = Post::class;
}
