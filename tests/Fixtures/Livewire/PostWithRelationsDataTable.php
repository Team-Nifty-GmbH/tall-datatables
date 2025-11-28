<?php

namespace Tests\Fixtures\Livewire;

use Tests\Fixtures\Models\Post;
use TeamNiftyGmbH\DataTable\DataTable;

class PostWithRelationsDataTable extends DataTable
{
    protected string $model = Post::class;

    public array $enabledCols = [
        'title',
        'content',
        'price',
        'is_published',
        'user.name',
        'user.email',
        'created_at',
    ];

    public array $availableRelations = [
        'user',
        'comments',
    ];

    public bool $isSelectable = true;

    public bool $isFilterable = true;
}
