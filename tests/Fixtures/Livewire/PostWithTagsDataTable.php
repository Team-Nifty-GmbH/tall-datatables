<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class PostWithTagsDataTable extends DataTable
{
    public array $availableRelations = [
        'tags',
    ];

    public array $enabledCols = [
        'title',
        'tags.name',
    ];

    protected string $model = Post::class;
}
