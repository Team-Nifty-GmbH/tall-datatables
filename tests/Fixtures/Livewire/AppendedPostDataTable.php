<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\AppendedPost;

class AppendedPostDataTable extends DataTable
{
    public array $availableRelations = [
        'user',
    ];

    public array $enabledCols = [
        'title',
        'user.name',
    ];

    protected string $model = AppendedPost::class;
}
