<?php

namespace Tests\Fixtures\Livewire;

use Illuminate\Database\Eloquent\Builder;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\SearchablePost;

class IdEnabledSearchableDataTable extends DataTable
{
    public array $enabledCols = [
        'id',
        'title',
        'content',
    ];

    protected string $model = SearchablePost::class;

    /**
     * Override to simulate a Scout search result with hits/scout_pagination.
     * Avoids needing a live Meilisearch instance for tests.
     */
    protected function buildSearch(bool $unpaginated = false): Builder
    {
        $query = SearchablePost::query();

        if ($this->search && ! $unpaginated) {
            $hits = SearchablePost::query()
                ->where('title', 'like', '%' . $this->search . '%')
                ->get()
                ->mapWithKeys(function (SearchablePost $post): array {
                    return [
                        $post->getKey() => [
                            'id' => $post->getKey(),
                            'title' => $post->title,
                            'content' => $post->content,
                            '_formatted' => [
                                'id' => (string) $post->getKey(),
                                'title' => str_ireplace($this->search, '<mark>' . $this->search . '</mark>', (string) $post->title),
                                'content' => (string) $post->content,
                            ],
                        ],
                    ];
                })
                ->all();

            $query->whereKey(array_keys($hits));
            $query->hits = $hits;
            $query->scout_pagination = [
                'estimatedTotalHits' => count($hits),
                'limit' => $this->perPage,
                'offset' => 0,
            ];
        }

        return $query;
    }
}
