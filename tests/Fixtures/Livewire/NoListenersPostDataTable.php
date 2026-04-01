<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use TeamNiftyGmbH\DataTable\Traits\HasEloquentListeners;
use Tests\Fixtures\Models\Post;

/**
 * Uses HasEloquentListeners with a model (Post) that does NOT use BroadcastsEvents.
 * This tests the "model without broadcasting" path.
 */
class NoListenersPostDataTable extends DataTable
{
    use HasEloquentListeners;

    public array $enabledCols = [
        'title',
        'content',
        'is_published',
        'created_at',
    ];

    protected string $model = Post::class;
}
