<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Controllers\SearchController;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\RestrictedColsPostDataTable;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\SearchablePost;
use Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    createTestPost(['user_id' => $this->user->getKey()]);
    createTestPost(['user_id' => $this->user->getKey()]);
});

function recordWrites(): Closure
{
    $writes = [];
    DB::listen(function ($query) use (&$writes): void {
        if (! preg_match('/^\s*select\b/i', $query->sql)) {
            $writes[] = $query->sql;
        }
    });
    // a write the database rejects never reaches the query log, the attempt still counts
    Event::listen(['eloquent.saving: *', 'eloquent.deleting: *'], function (string $event) use (&$writes): void {
        $writes[] = $event;
    });

    // storing the column layout is a write the table is supposed to make
    return function () use (&$writes): array {
        return array_values(array_filter(
            $writes,
            fn (string $write) => ! str_contains($write, 'datatable_user_settings')
                && ! str_contains($write, 'DatatableUserSetting')
        ));
    };
}

/**
 * Every segment of a path the client sends is called as a method on the model. A name
 * that is no relation reaches the query builder through __call and runs there, so only
 * the relations the relation tree itself offers may ever be called.
 */
it('never calls a path segment that is not a relation', function (string $path): void {
    $component = Livewire::test(PostDataTable::class);
    $writes = recordWrites();

    $result = $component->instance()->loadSlug($path);

    expect($writes())->toBe([])
        ->and(Post::query()->count())->toBe(2)
        ->and($result['cols'])->toBe([])
        ->and($result['relations'])->toBe([]);
})->with([
    'truncate',
    'delete',
    'update',
    'forceDelete',
    'increment',
    'touch',
    'getConnection',
    'user.truncate',
    'user.posts.forceDelete',
]);

it('never calls a column segment that is not a relation', function (string $col): void {
    $component = Livewire::test(PostDataTable::class);
    $writes = recordWrites();

    $component->call('storeColLayout', ['title', $col])
        ->call('sortTable', $col);

    expect($writes())->toBe([])
        ->and(Post::query()->count())->toBe(2);
})->with([
    'save.title',
    'push.title',
    'truncate.title',
    'truncate_count',
    'save_count',
]);

it('never calls a requested search relation that is not a relation', function (string $param): void {
    config(['scout.driver' => 'collection']);
    Route::post('/search/{model}', SearchController::class)->where('model', '.*');
    $writes = recordWrites();

    $this->postJson('/search/' . str_replace('\\', '/', SearchablePost::class), [$param => 'save'])
        ->assertOk();

    expect($writes())->toBe([])
        ->and(Post::query()->count())->toBe(2);
})->with(['with', 'whereHas', 'whereDoesntHave']);

it('rejects a model that the relation path does not lead to', function (?string $model, ?string $relation): void {
    $component = Livewire::test(PostDataTable::class);

    $result = $component->instance()->loadRelation($model, $relation);

    expect($result)->toBe(['cols' => [], 'relations' => [], 'displayPath' => []])
        ->and($component->instance()->loadedPath)->toBeNull();
})->with([
    'class that is no model' => [stdClass::class, null],
    'model other than the root' => [User::class, null],
    'model the relation does not return' => [User::class, 'comments'],
    'method that is no relation' => [Post::class, 'truncate'],
]);

it('offers the filter the relations of the root model while the column tree sits deeper', function (): void {
    $component = Livewire::test(PostDataTable::class);

    $component->instance()->loadSlug('user');

    expect($component->instance()->getFilterRelations())
        ->toHaveKeys(['user', 'comments'])
        ->not->toHaveKey('posts');
});

it('does not hand one table the cached relation tree of another', function (): void {
    $restricted = Livewire::test(RestrictedColsPostDataTable::class);
    Livewire::test(PostDataTable::class);

    $cols = collect($restricted->instance()->loadSlug()['cols'])->pluck('col')->all();

    expect($cols)->toBe(['title', 'content']);
});
