<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Comment;

class CommentWithPostUserDataTable extends DataTable
{
    public array $enabledCols = [
        'body',
        'post_user.name',
    ];

    protected string $model = Comment::class;
}
