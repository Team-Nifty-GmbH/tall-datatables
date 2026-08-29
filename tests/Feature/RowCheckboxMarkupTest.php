<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    Artisan::call('view:clear');

    $this->user = createTestUser();
    $this->actingAs($this->user);
});

it('emits the row checkbox as a plain input, not as the component', function (): void {
    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Selectable Row']);

    $html = preg_replace('/\s+/', ' ', Livewire::test(PostDataTable::class)->call('loadData')->html());

    $inputs = preg_match_all('/<input[^>]*type="checkbox"/', $html);
    // the component wraps every checkbox in a label for a label text the table
    // never passes, which is what made it four views per row. The select all
    // box in the head is still the component, the row boxes must not be.
    $wrapped = preg_match_all('/<label class="relative inline-flex cursor-pointer items-start">/', $html);

    expect($html)->toContain('$wire.toggleSelected(')
        ->and($inputs)->toBe(2)
        ->and($wrapped)->toBeLessThan($inputs);
});

it('keeps the row checkbox classes in step with the tallstackui component', function (): void {
    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Selectable Row']);

    $pattern = '/<input[^>]*type="checkbox"[^>]*class="([^"]*)"/';

    $component = preg_replace('/\s+/', ' ', Blade::render('<x-checkbox sm />'));
    preg_match($pattern, $component, $expected);

    $html = preg_replace('/\s+/', ' ', Livewire::test(PostDataTable::class)->call('loadData')->html());
    preg_match($pattern, $html, $actual);

    expect($expected[1] ?? 'component markup changed')
        ->not->toBe('component markup changed')
        ->and($actual[1] ?? 'no inlined checkbox')
        ->toBe($expected[1]);
});
