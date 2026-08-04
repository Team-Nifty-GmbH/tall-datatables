<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class ChainedToManyDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        // no to-many hop
        'user.name',
        // a single to-many hop, the supported case
        'comments.body',
        // three to-many hops: comments -> post -> comments -> user -> posts
        'comments.post.comments.user.posts.title',
    ];

    protected string $model = Post::class;
}
