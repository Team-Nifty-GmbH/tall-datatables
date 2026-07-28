<?php

namespace Tests\Fixtures\Livewire;

use Illuminate\Database\Eloquent\Builder;
use TeamNiftyGmbH\DataTable\DataTable;
use Tests\Fixtures\Models\SearchablePost;

class RankingScoreSearchableDataTable extends DataTable
{
    public array $enabledCols = [
        'id',
        'title',
        'content',
    ];

    protected string $model = SearchablePost::class;

    /**
     * Override to simulate a Meilisearch hybrid-search hit carrying a
     * per-row _rankingScore, without needing a live Meilisearch instance.
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
                            '_rankingScore' => 0.876,
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
