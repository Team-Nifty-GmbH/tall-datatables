<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class PostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
        'price',
        'is_published',
        'created_at',
    ];

    public bool $isFilterable = true;

    public bool $isSelectable = true;

    protected string $model = Post::class;
}
