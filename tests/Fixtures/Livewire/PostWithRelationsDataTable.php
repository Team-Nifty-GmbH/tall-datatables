<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class PostWithRelationsDataTable extends DataTable
{
    public array $availableRelations = [
        'user',
        'comments',
    ];

    public array $enabledCols = [
        'title',
        'content',
        'price',
        'is_published',
        'user.name',
        'user.email',
        'created_at',
    ];

    public bool $isFilterable = true;

    public bool $isSelectable = true;

    protected string $model = Post::class;
}
