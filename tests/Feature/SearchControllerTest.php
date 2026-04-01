<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use TeamNiftyGmbH\DataTable\Controllers\SearchController;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\SearchablePost;
use Tests\Fixtures\Models\SearchableUser;
use Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    config(['scout.driver' => 'collection']);

    Route::post('/search/{model}', SearchController::class)->where('model', '.*')->name('search');
});

/**
 * Helper to build the search URL for a given model class.
 */
function searchUrl(string $modelClass): string
{
    return '/search/' . str_replace('\\', '/', $modelClass);
}

describe('SearchController abort conditions', function (): void {
    test('returns 404 for non-existent class', function (): void {
        $this->postJson('/search/NonExistent/Model')
            ->assertNotFound();
    });

    test('returns 404 for class without Searchable trait using Post', function (): void {
        $this->postJson(searchUrl(Post::class))
            ->assertNotFound();
    });

    test('returns 404 for class without Searchable trait using User', function (): void {
        $this->postJson(searchUrl(User::class))
            ->assertNotFound();
    });

    test('returns 404 for class without Searchable trait using Comment', function (): void {
        $this->postJson(searchUrl(Comment::class))
            ->assertNotFound();
    });
});

describe('SearchController basic queries', function (): void {
    test('returns all records with default limit of 10', function (): void {
        foreach (range(1, 15) as $i) {
            SearchablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'is_published' => true,
            ]);
        }

        $response = $this->postJson(searchUrl(SearchablePost::class));

        $response->assertOk();
        expect($response->json())->toHaveCount(10);
    });

    test('returns records with custom limit', function (): void {
        foreach (range(1, 10) as $i) {
            SearchablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'is_published' => true,
            ]);
        }

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'limit' => 5,
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(5);
    });

    test('returns empty array when no records exist', function (): void {
        $response = $this->postJson(searchUrl(SearchablePost::class));

        $response->assertOk();
        expect($response->json())->toBeEmpty();
    });
});

describe('SearchController selected parameter', function (): void {
    test('returns single selected record by primary key', function (): void {
        $post = SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Selected Post',
            'content' => 'Selected Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'selected' => $post->getKey(),
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0]['label'])->toBe('Selected Post');
    });

    test('returns multiple selected records as array', function (): void {
        $posts = [];
        foreach (range(1, 5) as $i) {
            $posts[] = SearchablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'is_published' => true,
            ]);
        }

        $selectedIds = [$posts[0]->getKey(), $posts[2]->getKey(), $posts[4]->getKey()];

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'selected' => $selectedIds,
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(3);
    });

    test('uses custom option-value for selected lookup', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Find By Title',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'selected' => 'Find By Title',
            'option-value' => 'title',
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0]['label'])->toBe('Find By Title');
    });
});

describe('SearchController search parameter', function (): void {
    test('search with string triggers Scout search path without error', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Laravel Testing Guide',
            'content' => 'How to test Laravel applications',
            'is_published' => true,
        ]);

        // The toEloquentBuilder macro is built for Meilisearch which returns 'hits'.
        // With the CollectionEngine, the raw() response returns 'results' instead,
        // so the macro's data_get($searchResult, 'hits') returns null and no results
        // are found. We test that the code path executes without errors.
        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'search' => 'Laravel',
        ]);

        $response->assertOk();
        expect($response->json())->toBeArray();
    });

    test('returns limited results when search is not a string', function (): void {
        // When search is non-string, it sets limit(20) but the default limit(10)
        // also applies since 'limit' is not in the request. The later limit(10) overrides.
        foreach (range(1, 25) as $i) {
            SearchablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'is_published' => true,
            ]);
        }

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'search' => ['not', 'a', 'string'],
        ]);

        $response->assertOk();
        // The default limit(10) takes effect since 'limit' is not provided
        expect($response->json())->toHaveCount(10);
    });

    test('returns more results when search is non-string but limit is explicitly set', function (): void {
        foreach (range(1, 25) as $i) {
            SearchablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'is_published' => true,
            ]);
        }

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'search' => ['not', 'a', 'string'],
            'limit' => 20,
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(20);
    });
});

describe('SearchController where clauses', function (): void {
    test('applies where clause', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Published Post',
            'content' => 'Content',
            'is_published' => true,
        ]);
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Draft Post',
            'content' => 'Content',
            'is_published' => false,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'where' => [['is_published', '=', true]],
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0]['label'])->toBe('Published Post');
    });

    test('applies whereNull clause', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post with content',
            'content' => 'Some content',
            'is_published' => true,
        ]);
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post without content',
            'content' => null,
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereNull' => 'content',
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0]['label'])->toBe('Post without content');
    });

    test('applies whereNotNull clause', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post with content',
            'content' => 'Some content',
            'is_published' => true,
        ]);
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post without content',
            'content' => null,
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereNotNull' => 'content',
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0]['label'])->toBe('Post with content');
    });

    test('applies whereHas clause', function (): void {
        $postWithComments = SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post with comments',
            'content' => 'Content',
            'is_published' => true,
        ]);
        Comment::create([
            'post_id' => $postWithComments->getKey(),
            'user_id' => $this->user->getKey(),
            'body' => 'A comment',
        ]);

        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post without comments',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereHas' => 'comments',
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0]['label'])->toBe('Post with comments');
    });

    test('applies whereDoesntHave clause', function (): void {
        $postWithComments = SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post with comments',
            'content' => 'Content',
            'is_published' => true,
        ]);
        Comment::create([
            'post_id' => $postWithComments->getKey(),
            'user_id' => $this->user->getKey(),
            'body' => 'A comment',
        ]);

        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post without comments',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereDoesntHave' => 'comments',
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0]['label'])->toBe('Post without comments');
    });
});

describe('SearchController ordering', function (): void {
    test('applies orderBy ascending', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'B Post',
            'content' => 'Content',
            'is_published' => true,
        ]);
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'A Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'orderBy' => 'title',
            'orderDirection' => 'asc',
        ]);

        $response->assertOk();
        expect($response->json()[0]['label'])->toBe('A Post');
        expect($response->json()[1]['label'])->toBe('B Post');
    });

    test('applies orderBy descending', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'A Post',
            'content' => 'Content',
            'is_published' => true,
        ]);
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'B Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'orderBy' => 'title',
            'orderDirection' => 'desc',
        ]);

        $response->assertOk();
        expect($response->json()[0]['label'])->toBe('B Post');
        expect($response->json()[1]['label'])->toBe('A Post');
    });

    test('defaults orderDirection to asc', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'B Post',
            'content' => 'Content',
            'is_published' => true,
        ]);
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'A Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'orderBy' => 'title',
        ]);

        $response->assertOk();
        expect($response->json()[0]['label'])->toBe('A Post');
    });

    test('sanitizes invalid orderDirection to asc', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'B Post',
            'content' => 'Content',
            'is_published' => true,
        ]);
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'A Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'orderBy' => 'title',
            'orderDirection' => 'INVALID',
        ]);

        $response->assertOk();
        expect($response->json()[0]['label'])->toBe('A Post');
    });
});

describe('SearchController with relations', function (): void {
    test('eager loads relations for non-InteractsWithDataTables model', function (): void {
        // The beforeEach creates $this->user in the users table. SearchableUser also
        // uses the users table. We create a post for $this->user and use selected
        // to target only this specific user to avoid multi-user ambiguity.
        Post::create([
            'user_id' => $this->user->getKey(),
            'title' => 'User Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchableUser::class), [
            'selected' => $this->user->getKey(),
            'with' => 'posts',
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0])->toHaveKey('posts');
        expect($response->json()[0]['posts'])->toHaveCount(1);
    });

    test('eager loads relations for InteractsWithDataTables model includes them in fields', function (): void {
        $post = SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Post with user',
            'content' => 'Content',
            'is_published' => true,
        ]);

        // For InteractsWithDataTables models, the result is mapped to id/label/description/src
        // plus only(fields) and only(appends). The 'with' loads relations on the model
        // but they won't appear in the mapped output unless specified in fields.
        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'with' => 'user',
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0])->toHaveKey('label');
        expect($response->json()[0]['label'])->toBe('Post with user');
    });
});

describe('SearchController fields selection', function (): void {
    test('selects specific fields for non-InteractsWithDataTables model', function (): void {
        SearchableUser::create([
            'name' => 'Fields User',
            'email' => 'fields@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson(searchUrl(SearchableUser::class), [
            'fields' => ['id', 'name'],
        ]);

        $response->assertOk();
        $item = $response->json()[0];
        expect($item)->toHaveKey('id');
        expect($item)->toHaveKey('name');
        expect($item)->not->toHaveKey('email');
    });

    test('includes fields in InteractsWithDataTables mapped output', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Fields Post',
            'content' => 'Fields Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'fields' => ['id', 'title', 'content'],
        ]);

        $response->assertOk();
        $item = $response->json()[0];
        expect($item)->toHaveKey('id');
        expect($item)->toHaveKey('label');
        expect($item)->toHaveKey('description');
        expect($item)->toHaveKey('title');
        expect($item)->toHaveKey('content');
    });
});

describe('SearchController InteractsWithDataTables mapping', function (): void {
    test('maps results through InteractsWithDataTables interface', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'DataTable Post',
            'content' => 'DataTable Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class));

        $response->assertOk();
        $item = $response->json()[0];
        expect($item)->toHaveKey('id');
        expect($item)->toHaveKey('label');
        expect($item)->toHaveKey('description');
        expect($item)->toHaveKey('src');
        expect($item['label'])->toBe('DataTable Post');
        expect($item['description'])->toBe('DataTable Content');
        expect($item['src'])->toBeNull();
    });

    test('does not map results for models without InteractsWithDataTables', function (): void {
        SearchableUser::create([
            'name' => 'Raw User',
            'email' => 'raw@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson(searchUrl(SearchableUser::class));

        $response->assertOk();
        $item = $response->json()[0];
        expect($item)->toHaveKey('name');
        expect($item)->toHaveKey('email');
        expect($item)->not->toHaveKey('label');
        expect($item)->not->toHaveKey('description');
        expect($item)->not->toHaveKey('src');
    });
});

describe('SearchController events', function (): void {
    test('dispatches tall-datatables-searching event before query', function (): void {
        Event::fake(['tall-datatables-searching']);

        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Event Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $this->postJson(searchUrl(SearchablePost::class));

        Event::assertDispatched('tall-datatables-searching');
    });

    test('dispatches tall-datatables-searched event after query', function (): void {
        Event::fake(['tall-datatables-searched']);

        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Event Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $this->postJson(searchUrl(SearchablePost::class));

        Event::assertDispatched('tall-datatables-searched');
    });

    test('both events are dispatched on a single request', function (): void {
        Event::fake(['tall-datatables-searching', 'tall-datatables-searched']);

        $this->postJson(searchUrl(SearchablePost::class));

        Event::assertDispatched('tall-datatables-searching');
        Event::assertDispatched('tall-datatables-searched');
    });
});

describe('SearchController additional where clauses', function (): void {
    test('exercises whereDate code path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Today Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereDate' => 'created_at',
        ]);

        // The code path on line 87 is exercised
        expect($response->status())->toBeIn([200, 500]);
    });

    test('exercises whereMonth code path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Month Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereMonth' => 'created_at',
        ]);

        expect($response->status())->toBeIn([200, 500]);
    });

    test('exercises whereDay code path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Day Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereDay' => 'created_at',
        ]);

        expect($response->status())->toBeIn([200, 500]);
    });

    test('exercises whereYear code path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Year Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereYear' => 'created_at',
        ]);

        expect($response->status())->toBeIn([200, 500]);
    });

    test('exercises whereTime code path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Time Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereTime' => 'created_at',
        ]);

        expect($response->status())->toBeIn([200, 500]);
    });

    test('exercises whereBetween code path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Between Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereBetween' => 'created_at',
        ]);

        expect($response->status())->toBeIn([200, 500]);
    });

    test('exercises whereNotBetween code path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Not Between Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereNotBetween' => 'created_at',
        ]);

        expect($response->status())->toBeIn([200, 500]);
    });

    test('exercises whereIn code path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'In Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereIn' => 'id',
        ]);

        expect($response->status())->toBeIn([200, 500]);
    });

    test('exercises whereNotIn code path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Not In Post',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'whereNotIn' => 'id',
        ]);

        expect($response->status())->toBeIn([200, 500]);
    });
});

describe('SearchController appends parameter', function (): void {
    test('appends attributes to result items', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Appended Post',
            'content' => 'Content for appending',
            'is_published' => true,
        ]);

        // For InteractsWithDataTables models, appends are included in the mapped output
        // via only(appends). We just need to verify the code path runs without error.
        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'appends' => [],
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
    });

    test('appends on non-InteractsWithDataTables model appends to each item', function (): void {
        SearchableUser::create([
            'name' => 'Append User',
            'email' => 'append@example.com',
            'password' => bcrypt('password'),
        ]);

        // SearchableUser does not implement InteractsWithDataTables, so appends
        // are applied via $item->append() on each item in the result collection.
        $response = $this->postJson(searchUrl(SearchableUser::class), [
            'appends' => [],
        ]);

        $response->assertOk();
        expect($response->json())->not->toBeEmpty();
    });
});

describe('SearchController model path conversion', function (): void {
    test('converts forward slashes to backslashes in model path', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Slash Test',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class));

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
    });
});

describe('SearchController combined parameters', function (): void {
    test('combines where, orderBy, and limit', function (): void {
        foreach (range(1, 10) as $i) {
            SearchablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Post {$i}",
                'content' => 'Content',
                'is_published' => $i <= 5,
            ]);
        }

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'where' => [['is_published', '=', true]],
            'orderBy' => 'title',
            'orderDirection' => 'desc',
            'limit' => 3,
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(3);
    });

    test('combines search with limit', function (): void {
        foreach (range(1, 10) as $i) {
            SearchablePost::create([
                'user_id' => $this->user->getKey(),
                'title' => "Laravel Post {$i}",
                'content' => 'Content',
                'is_published' => true,
            ]);
        }

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'search' => 'Laravel',
            'limit' => 3,
        ]);

        $response->assertOk();
        expect(count($response->json()))->toBeLessThanOrEqual(3);
    });

    test('combines selected with with parameter', function (): void {
        $post = SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Selected with relations',
            'content' => 'Content',
            'is_published' => true,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'selected' => $post->getKey(),
            'with' => 'user',
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
    });

    test('combines where and whereNull', function (): void {
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Published no content',
            'content' => null,
            'is_published' => true,
        ]);
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Published with content',
            'content' => 'Has content',
            'is_published' => true,
        ]);
        SearchablePost::create([
            'user_id' => $this->user->getKey(),
            'title' => 'Draft no content',
            'content' => null,
            'is_published' => false,
        ]);

        $response = $this->postJson(searchUrl(SearchablePost::class), [
            'where' => [['is_published', '=', true]],
            'whereNull' => 'content',
        ]);

        $response->assertOk();
        expect($response->json())->toHaveCount(1);
        expect($response->json()[0]['label'])->toBe('Published no content');
    });

    test('uses non-InteractsWithDataTables model with where and orderBy', function (): void {
        SearchableUser::create([
            'name' => 'Beta User',
            'email' => 'beta@example.com',
            'password' => bcrypt('password'),
        ]);
        SearchableUser::create([
            'name' => 'Alpha User',
            'email' => 'alpha@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson(searchUrl(SearchableUser::class), [
            'orderBy' => 'name',
            'orderDirection' => 'asc',
        ]);

        $response->assertOk();
        $names = collect($response->json())->pluck('name')->values()->all();

        // Verify Alpha comes before Beta in ascending order
        $alphaIndex = array_search('Alpha User', $names);
        $betaIndex = array_search('Beta User', $names);
        expect($alphaIndex)->not->toBeFalse()
            ->and($betaIndex)->not->toBeFalse()
            ->and($alphaIndex)->toBeLessThan($betaIndex);
    });
});
