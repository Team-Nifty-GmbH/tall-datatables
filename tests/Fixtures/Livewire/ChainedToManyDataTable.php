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
        // two to-many hops through distinct models, a list of a list
        'comments.tags.name',
        // two to-many hops that return to the model the path started on
        'comments.post.comments.body',
        // three to-many hops: comments -> post -> comments -> user -> posts
        'comments.post.comments.user.posts.title',
    ];

    protected string $model = Post::class;
}
