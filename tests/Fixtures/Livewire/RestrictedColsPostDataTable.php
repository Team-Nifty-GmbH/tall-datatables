<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class RestrictedColsPostDataTable extends DataTable
{
    public array $availableCols = [
        'title',
        'content',
    ];

    public array $enabledCols = [
        'title',
        'content',
    ];

    public bool $isFilterable = true;

    protected string $model = Post::class;
}
