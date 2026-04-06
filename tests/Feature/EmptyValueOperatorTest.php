<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Empty value operators', function (): void {
    describe('= without value treated as is null', function (): void {
        it('finds records with null value when filtering with =', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Description', 'content' => 'Some text']);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Description', 'content' => null]);

            $component = Livewire::test(PostDataTable::class)
                ->call('loadData')
                ->set('userFilters', [
                    [['column' => 'content', 'operator' => 'is null', 'value' => null]],
                ]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            $nullCount = $data['total'];

            // Now test with = (no value) via text filter — should get same result
            $fresh = Livewire::test(PostDataTable::class)
                ->call('loadData');

            // Simulate text input of just "=" in the content column
            $reflection = new ReflectionMethod($fresh->instance(), 'parseTextFilterValue');
            $parsed = $reflection->invoke($fresh->instance(), '=', 'content');

            expect($parsed['operator'])->toBe('is null')
                ->and($parsed['value'])->toBeNull();
        });

        it('parses = as is null operator', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $reflection = new ReflectionMethod($component->instance(), 'parseTextFilterValue');
            $parsed = $reflection->invoke($component->instance(), '=', 'title');

            expect($parsed['operator'])->toBe('is null')
                ->and($parsed['value'])->toBeNull();
        });

        it('parses = with whitespace as is null operator', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $reflection = new ReflectionMethod($component->instance(), 'parseTextFilterValue');
            $parsed = $reflection->invoke($component->instance(), '=  ', 'title');

            expect($parsed['operator'])->toBe('is null')
                ->and($parsed['value'])->toBeNull();
        });
    });

    describe('!= without value treated as is not null', function (): void {
        it('parses != as is not null operator', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $reflection = new ReflectionMethod($component->instance(), 'parseTextFilterValue');
            $parsed = $reflection->invoke($component->instance(), '!=', 'title');

            expect($parsed['operator'])->toBe('is not null')
                ->and($parsed['value'])->toBeNull();
        });

        it('parses != with whitespace as is not null operator', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $reflection = new ReflectionMethod($component->instance(), 'parseTextFilterValue');
            $parsed = $reflection->invoke($component->instance(), '!=  ', 'title');

            expect($parsed['operator'])->toBe('is not null')
                ->and($parsed['value'])->toBeNull();
        });
    });

    describe('integration', function (): void {
        it('= filter returns only null records', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'With Desc', 'content' => 'text']);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Without Desc', 'content' => null]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('userFilters', [
                [['column' => 'content', 'operator' => 'is null', 'value' => null]],
            ]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data['total'])->toBe(1);

            $titles = collect($data['data'])->pluck('title')->toArray();
            expect($titles)->toContain('Without Desc');
        });

        it('!= filter returns only non-null records', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'With Desc', 'content' => 'text']);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Without Desc', 'content' => null]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('userFilters', [
                [['column' => 'content', 'operator' => 'is not null', 'value' => null]],
            ]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data['total'])->toBe(1);

            $titles = collect($data['data'])->pluck('title')->toArray();
            expect($titles)->toContain('With Desc');
        });
    });
});
