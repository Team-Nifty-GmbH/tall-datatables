<?php

namespace Tests\Fixtures\Livewire;

use Tests\Fixtures\Models\User;
use TeamNiftyGmbH\DataTable\DataTable;

class UserDataTable extends DataTable
{
    protected string $model = User::class;

    public array $enabledCols = [
        'name',
        'email',
        'created_at',
    ];

    public bool $isSelectable = true;

    public bool $isFilterable = true;
}
