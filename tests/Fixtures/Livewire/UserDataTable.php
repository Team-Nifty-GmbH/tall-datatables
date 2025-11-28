<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\User;

class UserDataTable extends DataTable
{
    public array $enabledCols = [
        'name',
        'email',
        'created_at',
    ];

    public bool $isFilterable = true;

    public bool $isSelectable = true;

    protected string $model = User::class;
}
