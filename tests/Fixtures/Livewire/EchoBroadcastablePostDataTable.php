<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use TeamNiftyGmbH\DataTable\DataTable;
use TeamNiftyGmbH\DataTable\Traits\HasEloquentListeners;
use Tests\Fixtures\Models\BroadcastablePost;

#[Layout('components.layouts.echo-app')]
class EchoBroadcastablePostDataTable extends DataTable
{
    use HasEloquentListeners;

    public array $enabledCols = [
        'title',
        'content',
        'is_published',
        'created_at',
    ];

    protected string $model = BroadcastablePost::class;
}
