<?php

use Illuminate\Support\Facades\Route;
use TeamNiftyGmbH\DataTable\Controllers\SearchController;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    Route::post('/search/{model}', SearchController::class)->where('model', '.*')->name('search');
});

describe('SearchController abort conditions', function (): void {
    test('returns 404 for non-existent class', function (): void {
        $this->postJson('/search/NonExistent/Model')
            ->assertNotFound();
    });

    test('returns 404 for class without Searchable trait using Post', function (): void {
        $this->postJson('/search/' . str_replace('\\', '/', Post::class))
            ->assertNotFound();
    });

    test('returns 404 for class without Searchable trait using User', function (): void {
        $this->postJson('/search/' . str_replace('\\', '/', User::class))
            ->assertNotFound();
    });
});
