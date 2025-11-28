<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createTestUser(array $attributes = []): Tests\Fixtures\Models\User
{
    return Tests\Fixtures\Models\User::create(array_merge([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ], $attributes));
}

function createTestPost(array $attributes = []): Tests\Fixtures\Models\Post
{
    $user = $attributes['user_id'] ?? createTestUser()->getKey();

    return Tests\Fixtures\Models\Post::create(array_merge([
        'user_id' => $user,
        'title' => fake()->sentence(),
        'content' => fake()->paragraphs(3, true),
        'is_published' => fake()->boolean(),
    ], $attributes));
}

function createTestProduct(array $attributes = []): Tests\Fixtures\Models\Product
{
    $user = $attributes['user_id'] ?? createTestUser()->getKey();

    return Tests\Fixtures\Models\Product::create(array_merge([
        'user_id' => $user,
        'name' => fake()->words(3, true),
        'description' => fake()->sentence(),
        'price' => fake()->randomFloat(2, 10, 1000),
        'discount' => fake()->randomFloat(4, 0, 1),
        'quantity' => fake()->randomFloat(2, 1, 100),
        'website' => fake()->url(),
        'image_url' => fake()->imageUrl(),
        'is_active' => fake()->boolean(),
        'metadata' => ['sku' => fake()->uuid(), 'category' => fake()->word()],
    ], $attributes));
}

function createTestComment(array $attributes = []): Tests\Fixtures\Models\Comment
{
    $user = $attributes['user_id'] ?? createTestUser()->getKey();
    $post = $attributes['post_id'] ?? createTestPost(['user_id' => $user])->getKey();

    return Tests\Fixtures\Models\Comment::create(array_merge([
        'user_id' => $user,
        'post_id' => $post,
        'body' => fake()->paragraph(),
    ], $attributes));
}

/**
 * Create a dynamic route to a Livewire component and visit it in the browser.
 * This is useful for testing full-page Livewire components in browser tests.
 *
 * @param  string  $component  The Livewire component class or name
 * @param  array<string, mixed>  $options  Options to pass to the visit function
 * @return mixed The browser page instance
 */
function visitLivewire(string $component, array $options = []): mixed
{
    $uri = '/livewire-test/' . uniqid();

    Route::get($uri, $component);

    return visit($uri, $options);
}
