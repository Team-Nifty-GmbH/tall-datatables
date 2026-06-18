<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

/**
 * Data table without HasEloquentListeners rendered with window.Echo present.
 * Used to ensure the echo listener setup does not probe the missing
 * broadcastChannels property, which Livewire would treat as a method call.
 */
#[Layout('components.layouts.echo-app')]
class EchoPlainPostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
        'is_published',
        'created_at',
    ];

    protected string $model = Post::class;
}
