<?php

namespace Tests\Fixtures\Livewire;

use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;
use TeamNiftyGmbH\DataTable\DataTable;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableRowAttributes;
use Tests\Fixtures\Models\Post;

class CellAttributesPostDataTable extends DataTable
{
    public array $enabledCols = [
        'title',
        'content',
    ];

    protected string $model = Post::class;

    protected function getCellAttributes(): ComponentAttributeBag
    {
        return DataTableRowAttributes::make()
            ->bind('class', '\'whitespace-normal\'');
    }

    protected function itemToArray($item): array
    {
        $item = parent::itemToArray($item);

        $raw = is_array($item['content']) ? $item['content']['raw'] : $item['content'];
        $item['content'] = new HtmlString(nl2br(e((string) $raw)));

        return $item;
    }
}
