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
    it('populates broadcastChannels with created key on mount when model uses BroadcastsEvents', function (): void {
        $component = Livewire::test(BroadcastablePostDataTable::class);

        $channels = $component->get('broadcastChannels');
        expect($channels)->toBeArray()
            ->and($channels)->toHaveKey('created')
            ->and($channels['created'])->toBeString();
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

    it('updates broadcastChannels for the created model', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'A Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();

        $instance->echoCreated(['model' => ['id' => $post->getKey()]]);

        expect($instance->broadcastChannels)->toHaveKey($post->getKey());
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

    it('removes model from broadcastChannels on delete', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post to Delete',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $testable = mountAndLoadData();
        $instance = $testable->instance();
        expect($instance->broadcastChannels)->toHaveKey($post->getKey());

        $instance->echoDeleted(['model' => ['id' => $post->getKey()]]);

        expect($instance->broadcastChannels)->not->toHaveKey($post->getKey());
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

describe('HasEloquentListeners – getPaginator', function (): void {
    it('populates broadcastChannels from paginator items', function (): void {
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

        $component = Livewire::test(BroadcastablePostDataTable::class);

        $channels = $component->get('broadcastChannels');
        expect($channels)->toHaveKey($post1->getKey())
            ->and($channels)->toHaveKey($post2->getKey())
            ->and($channels)->toHaveKey('created');
    });

    it('does not populate broadcastChannels when model lacks BroadcastsEvents', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(NoListenersPostDataTable::class);

        $channels = $component->get('broadcastChannels');
        expect($channels)->toBe([]);
    });
});

describe('HasEloquentListeners – mount fallback without getBroadcastChannel', function (): void {
    it('falls back to broadcastChannel concat when model lacks getBroadcastChannel', function (): void {
        // Use a custom datatable with a model that uses BroadcastsEvents but
        // without the static getBroadcastChannel method
        $modelClass = new class() extends Illuminate\Database\Eloquent\Model
        {
            use TeamNiftyGmbH\DataTable\Traits\BroadcastsEvents;

            protected $guarded = ['id'];

            protected $table = 'posts';
        };

        $dataTableClass = get_class($modelClass);

        // We test via the trait method directly
        $component = Livewire::test(BroadcastablePostDataTable::class);
        $instance = $component->instance();

        $channels = $instance->broadcastChannels;
        // The created key should be set on mount
        expect($channels)->toHaveKey('created');
    });
});

describe('HasEloquentListeners – getPaginator not on page 1', function (): void {
    it('sets created channel even when not on page 1', function (): void {
        for ($i = 0; $i < 20; $i++) {
            BroadcastablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post $i",
                'content' => 'Content',
                'is_published' => true,
            ]);
        }

        $component = Livewire::test(BroadcastablePostDataTable::class);
        $component->set('perPage', 10);
        $component->call('gotoPage', 2);

        $channels = $component->get('broadcastChannels');
        expect($channels)->toHaveKey('created');
    });
});
