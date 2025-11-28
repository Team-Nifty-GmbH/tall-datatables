<?php

namespace Tests\Fixtures\Livewire;

use Tests\Fixtures\Models\Post;
use TeamNiftyGmbH\DataTable\DataTable;

class PostDataTable extends DataTable
{
    protected string $model = Post::class;

    public array $enabledCols = [
        'title',
        'content',
        'price',
        'is_published',
        'created_at',
    ];

    public bool $isSelectable = true;

    public bool $isFilterable = true;
}
