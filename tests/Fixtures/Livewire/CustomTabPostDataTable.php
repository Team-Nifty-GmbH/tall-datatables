<?php

namespace Tests\Fixtures\Livewire;

use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\Post;

class CustomTabPostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
        'is_published',
        'created_at',
    ];

    protected string $model = Post::class;

    protected function getCustomSidebarTabs(): array
    {
        return [
            [
                'id' => 'custom-analytics',
                'label' => __('Analytics'),
                'view' => 'custom-tab',
            ],
        ];
    }
}
