<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

#[Layout('components.layouts.app')]
class CustomCacheKeyPostDataTable extends DataTable
{
    #[Locked]
    public ?string $cacheKey = 'custom-cache-key';

    public array $enabledCols = [
        'title',
        'content',
        'price',
        'is_published',
        'created_at',
    ];

    protected string $model = Post::class;
}
