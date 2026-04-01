<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class PostWithCommentsDataTable extends DataTable
{
    public array $availableRelations = [
        'user',
        'comments',
    ];

    public array $enabledCols = [
        'title',
        'content',
        'comments.body',
    ];

    public bool $isFilterable = true;

    protected string $model = Post::class;
}
