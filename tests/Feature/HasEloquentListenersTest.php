<?php

use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\BroadcastablePostDataTable;
use Tests\Fixtures\Livewire\NoListenersPostDataTable;
use Tests\Fixtures\Models\BroadcastablePost;

beforeEach(function (): void {
    Queue::fake();
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

/**
 * Helper: mount the component via Livewire, then grab the instance
 * and call loadData() so $this->data is populated for echo method tests.
 */
function mountAndLoadData(): \Livewire\Features\SupportTesting\Testable
{
    $testable = Livewire::test(BroadcastablePostDataTable::class);
    // loadData is called during mount, but dehydrate clears data.
    // Call refreshData via Livewire so it triggers in a request context.
    // Instead, we directly populate data on the instance.
    $instance = $testable->instance();
    $instance->loadData();

    return $testable;
}

describe('HasEloquentListeners – mount', function (): void {
    it('subscribes only the channel of the model on mount when model uses BroadcastsEvents', function (): void {
        $component = Livewire::test(BroadcastablePostDataTable::class);

        expect($component->get('broadcastChannels'))
            ->toBe(['model' => BroadcastablePost::getBroadcastChannel(true)]);
    });

    it('does not populate broadcastChannels when model does not use BroadcastsEvents', function (): void {
        $component = Livewire::test(NoListenersPostDataTable::class);

        $channels = $component->get('broadcastChannels');
        expect($channels)->toBe([]);
    });
});

describe('HasEloquentListeners – getEloquentListeners', function (): void {
    it('returns event-to-method mapping when model uses BroadcastsEvents', function (): void {
        $component = Livewire::test(BroadcastablePostDataTable::class);

        $listeners = $component->instance()->getEloquentListeners();
        expect($listeners)->toBeArray()
            ->and($listeners)->toHaveKey('.BroadcastablePostCreated', 'echoCreated')
            ->and($listeners)->toHaveKey('.BroadcastablePostUpdated', 'echoUpdated')
            ->and($listeners)->toHaveKey('.BroadcastablePostDeleted', 'echoDeleted')
            ->and($listeners)->toHaveKey('.BroadcastablePostTrashed', 'echoTrashed')
            ->and($listeners)->toHaveKey('.BroadcastablePostRestored', 'echoRestored');
    });

    it('returns empty array when model does not use BroadcastsEvents', function (): void {
        $component = Livewire::test(NoListenersPostDataTable::class);

        $listeners = $component->instance()->getEloquentListeners();
        expect($listeners)->toBe([]);
    });
});

describe('HasEloquentListeners – echoCreated', function (): void {
    it('adds a newly created model to the data array', function (): void {
        BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'First Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $dataBefore = $instance->data;
        expect($dataBefore['total'])->toBe(1);

        $newPost = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'New Post via Echo',
            'content' => 'New content',
            'is_published' => false,
        ]);

        $instance->echoCreated(['model' => ['id' => $newPost->getKey()]]);

        expect($instance->data['total'])->toBe(2)
            ->and($instance->data['data'][0]['title'])->toBe('New Post via Echo');
    });

    it('does not add model if it does not match the current query', function (): void {
        BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Existing Post',
            'content' => 'Existing content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        // Force a non-existent key
        $instance->echoCreated(['model' => ['id' => 99999]]);

        expect($instance->data['total'])->toBe(1);
    });

    it('does not add a created model when another page is shown', function (): void {
        for ($i = 0; $i < 20; $i++) {
            BroadcastablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => 'Content',
                'is_published' => true,
            ]);
        }

        $testable = Livewire::test(BroadcastablePostDataTable::class)
            ->set('perPage', 10)
            ->call('gotoPage', 2);
        $instance = $testable->instance();
        $instance->loadData();
        $rows = $instance->data['data'];

        $newPost = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Not on this page',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $instance->echoCreated(['model' => ['id' => $newPost->getKey()]]);

        expect($instance->data['data'])->toBe($rows)
            ->and($instance->data['total'])->toBe(20);
    });

    it('pops the last element when data exceeds per_page', function (): void {
        for ($i = 0; $i < 15; $i++) {
            BroadcastablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => 'Content',
                'is_published' => true,
            ]);
        }

        $testable = mountAndLoadData();
        $instance = $testable->instance();
        expect(count($instance->data['data']))->toBe(15);

        $newPost = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Overflow Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $instance->echoCreated(['model' => ['id' => $newPost->getKey()]]);

        expect(count($instance->data['data']))->toBe(15) // still 15, not 16
            ->and($instance->data['total'])->toBe(16)
            ->and($instance->data['data'][0]['title'])->toBe('Overflow Post');
    });

    it('sets from to 1 when it was previously falsy', function (): void {
        $testable = mountAndLoadData();
        $instance = $testable->instance();
        expect($instance->data['from'])->toBeFalsy();

        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'First Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $instance->echoCreated(['model' => ['id' => $post->getKey()]]);

        expect($instance->data['from'])->toBe(1);
    });
});

describe('HasEloquentListeners – echoUpdated', function (): void {
    it('updates an existing model in the data array', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Original Title',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $post->update(['title' => 'Updated Title']);

        $instance->echoUpdated(['model' => ['id' => $post->getKey()]]);

        $found = collect($instance->data['data'])->firstWhere('id', $post->getKey());
        expect($found['title'])->toBe('Updated Title');
    });

    it('removes model from data if it no longer matches the query', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'A Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();
        expect($instance->data['total'])->toBe(1);

        $post->forceDelete();

        $instance->echoUpdated(['model' => ['id' => $post->getKey()]]);

        expect($instance->data['total'])->toBe(0);
    });

    it('ignores an updated model that is not shown', function (): void {
        BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Shown Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $instance->echoUpdated(['model' => ['id' => 99999]]);

        expect($instance->data['total'])->toBe(1)
            ->and($instance->data['data'])->toHaveCount(1);
    });
});

describe('HasEloquentListeners – echoDeleted', function (): void {
    it('removes a deleted model from the data array', function (): void {
        $post1 = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post 1',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $post2 = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post 2',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();
        expect($instance->data['total'])->toBe(2);

        $instance->echoDeleted(['model' => ['id' => $post1->getKey()]]);

        expect($instance->data['total'])->toBe(1)
            ->and($instance->data['to'])->toBe(1);

        $remaining = collect($instance->data['data'])->pluck('id')->all();
        expect($remaining)->not->toContain($post1->getKey())
            ->and($remaining)->toContain($post2->getKey());
    });

    it('ignores a deleted model that is not shown', function (): void {
        BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Shown Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $instance->echoDeleted(['model' => ['id' => 99999]]);

        expect($instance->data['total'])->toBe(1)
            ->and($instance->data['to'])->toBe(1)
            ->and($instance->data['data'])->toHaveCount(1);
    });

    it('clamps total and to to zero when they are null instead of decrementing null', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Null Counters',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        // Pagination counters can be null in some payloads; decrementing null
        // raises an error on PHP 8.5.
        $data = $instance->data;
        $data['total'] = null;
        $data['to'] = null;
        $instance->data = $data;

        $instance->echoDeleted(['model' => ['id' => $post->getKey()]]);

        expect($instance->data['total'])->toBe(0)
            ->and($instance->data['to'])->toBe(0);
    });
});

describe('HasEloquentListeners – echoTrashed and echoRestored', function (): void {
    it('echoTrashed delegates to echoDeleted', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post to Trash',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();
        expect($instance->data['total'])->toBe(1);

        $instance->echoTrashed(['model' => ['id' => $post->getKey()]]);

        expect($instance->data['total'])->toBe(0);
    });

    it('echoRestored delegates to echoCreated', function (): void {
        BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Existing Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $newPost = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Restored Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $instance->echoRestored(['model' => ['id' => $newPost->getKey()]]);

        expect($instance->data['data'][0]['title'])->toBe('Restored Post');
    });
});

describe('HasEloquentListeners – eloquentEventOccurred', function (): void {
    it('routes a created event to echoCreated', function (): void {
        BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Event Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $newPost = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Via Event',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $instance->eloquentEventOccurred('.BroadcastablePostCreated', ['model' => ['id' => $newPost->getKey()]]);

        expect($instance->data['data'][0]['title'])->toBe('Via Event');
    });

    it('routes an updated event to echoUpdated', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Original',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $post->update(['title' => 'Modified']);

        $instance->eloquentEventOccurred('.BroadcastablePostUpdated', ['model' => ['id' => $post->getKey()]]);

        $found = collect($instance->data['data'])->firstWhere('id', $post->getKey());
        expect($found['title'])->toBe('Modified');
    });

    it('routes a deleted event to echoDeleted', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'To Delete',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $instance->eloquentEventOccurred('.BroadcastablePostDeleted', ['model' => ['id' => $post->getKey()]]);

        expect($instance->data['total'])->toBe(0);
    });

    it('ignores unknown events', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Safe Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $instance->eloquentEventOccurred('.BroadcastablePostUnknownEvent', ['model' => ['id' => $post->getKey()]]);

        expect($instance->data['total'])->toBe(1);
    });
});

describe('HasEloquentListeners – refreshData', function (): void {
    it('reloads the full dataset', function (): void {
        BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Original',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();
        expect($instance->data['total'])->toBe(1);

        BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'New',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $instance->refreshData();

        expect($instance->data['total'])->toBe(2);
    });
});

describe('HasEloquentListeners – refreshRow', function (): void {
    it('refreshes a single row in the data array', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Before Refresh',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $post->update(['title' => 'After Refresh']);

        $instance->refreshRow(['model' => ['id' => $post->getKey()]]);

        $found = collect($instance->data['data'])->firstWhere('id', $post->getKey());
        expect($found['title'])->toBe('After Refresh');
    });

    it('removes the row if model no longer matches query', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Will be deleted',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $post->forceDelete();

        $instance->refreshRow(['model' => ['id' => $post->getKey()]]);

        expect($instance->data['total'])->toBe(0);
    });

    it('returns early when key is null', function (): void {
        BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Existing',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $instance->refreshRow(['model' => []]);

        expect($instance->data['total'])->toBe(1);
    });
});

describe('HasEloquentListeners – broadcast channels', function (): void {
    it('subscribes the channel of the model instead of one channel per row', function (): void {
        for ($i = 0; $i < 3; $i++) {
            BroadcastablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => 'Content',
                'is_published' => true,
            ]);
        }

        $component = Livewire::test(BroadcastablePostDataTable::class);

        expect($component->get('broadcastChannels'))
            ->toBe(['model' => BroadcastablePost::getBroadcastChannel(true)]);
    });

    it('keeps the channel of the model on later pages', function (): void {
        for ($i = 0; $i < 20; $i++) {
            BroadcastablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => 'Content',
                'is_published' => true,
            ]);
        }

        $component = Livewire::test(BroadcastablePostDataTable::class)
            ->set('perPage', 10)
            ->call('gotoPage', 2);

        expect($component->get('broadcastChannels'))
            ->toBe(['model' => BroadcastablePost::getBroadcastChannel(true)]);
    });

    it('does not populate broadcastChannels when model lacks BroadcastsEvents', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(NoListenersPostDataTable::class);

        expect($component->get('broadcastChannels'))->toBe([]);
    });

    it('broadcasts updates on the record channel and the channel of the model', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        expect(array_map(fn ($channel): string => $channel->name, $post->broadcastOn('updated')))
            ->toBe([
                'private-' . $post->broadcastChannel(),
                'private-' . BroadcastablePost::getBroadcastChannel(true),
            ])
            ->and(array_map(fn ($channel): string => $channel->name, $post->broadcastOn('created')))
            ->toBe(['private-' . BroadcastablePost::getBroadcastChannel(true)]);
    });
});

describe('HasEloquentListeners – broadcastRowKeys', function (): void {
    it('exposes the keys of the shown rows for the browser filter', function (): void {
        $posts = collect(range(1, 3))->map(fn (int $i): BroadcastablePost => BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => "Post {$i}",
            'content' => 'Content',
            'is_published' => true,
        ]));

        $component = Livewire::test(BroadcastablePostDataTable::class);

        expect($component->get('broadcastRowKeys'))
            ->toEqualCanonicalizing($posts->map->getKey()->all());
    });

    it('collects the keys of rows inside groups', function (): void {
        $testable = Livewire::test(BroadcastablePostDataTable::class);
        $instance = $testable->instance();

        (fn () => $this->setData([
            'data' => [],
            'groups' => [
                ['data' => [['id' => 4], ['id' => 5]]],
                ['data' => [['id' => 6]]],
                ['count' => 2],
            ],
        ]))->call($instance);

        expect($instance->broadcastRowKeys)->toBe([4, 5, 6]);
    });
});
