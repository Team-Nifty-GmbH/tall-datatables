<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Enums\PostStatus;
use Tests\Fixtures\Models\Post;

class PostWithCommentStatusDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'comments.body',
    ];

    protected string $model = Post::class;

    public function getFormatters(): array
    {
        return ['comments.body' => PostStatus::class];
    }
}
