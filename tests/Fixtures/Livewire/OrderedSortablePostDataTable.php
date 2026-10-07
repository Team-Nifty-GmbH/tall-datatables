<?php

namespace Tests\Fixtures\Livewire;

use Illuminate\Database\Eloquent\Builder;

class OrderedSortablePostDataTable extends SortablePostDataTable
{
    protected function getBuilder(Builder $builder): Builder
    {
        return $builder->orderBy('title');
    }
}
