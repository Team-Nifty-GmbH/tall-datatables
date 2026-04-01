<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\SortablePostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('SupportsSorting', function (): void {
    describe('isSortable', function (): void {
        it('returns true when overridden in the component', function (): void {
            $component = Livewire::test(SortablePostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'isSortable');

            expect($reflection->invoke($component->instance()))->toBeTrue();
        });
    });

    describe('sortRows', function (): void {
        it('can be called as a Livewire action', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SortablePostDataTable::class)
                ->call('sortRows', $post->getKey(), 1);

            expect($component->get('sortedRows'))->toHaveCount(1);
        });

        it('tracks the record id and new position', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SortablePostDataTable::class)
                ->call('sortRows', $post->getKey(), 5);

            $sorted = $component->get('sortedRows');

            expect($sorted[0]['id'])->toBe($post->getKey())
                ->and($sorted[0]['position'])->toBe(5);
        });

        it('can be called with integer record id', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SortablePostDataTable::class)
                ->call('sortRows', $post->getKey(), 0);

            expect($component->get('sortedRows'))->toHaveCount(1);
        });

        it('can be called with string record id', function (): void {
            $component = Livewire::test(SortablePostDataTable::class)
                ->call('sortRows', 'uuid-string-id', 3);

            expect($component->get('sortedRows')[0])
                ->toBe(['id' => 'uuid-string-id', 'position' => 3]);
        });

        it('accumulates multiple sort operations', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SortablePostDataTable::class)
                ->call('sortRows', $post1->getKey(), 1)
                ->call('sortRows', $post2->getKey(), 2)
                ->call('sortRows', $post1->getKey(), 3);

            expect($component->get('sortedRows'))->toHaveCount(3);
        });

        it('accepts zero position', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SortablePostDataTable::class)
                ->call('sortRows', $post->getKey(), 0);

            expect($component->get('sortedRows')[0]['position'])->toBe(0);
        });

        it('accepts large position values', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SortablePostDataTable::class)
                ->call('sortRows', $post->getKey(), 9999);

            expect($component->get('sortedRows')[0]['position'])->toBe(9999);
        });
    });

    describe('sortedRows tracking', function (): void {
        it('starts as empty array', function (): void {
            $component = Livewire::test(SortablePostDataTable::class);

            expect($component->get('sortedRows'))->toBe([]);
        });

        it('preserves order of sort operations', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SortablePostDataTable::class)
                ->call('sortRows', $post1->getKey(), 2)
                ->call('sortRows', $post2->getKey(), 1);

            $sorted = $component->get('sortedRows');

            expect($sorted[0]['id'])->toBe($post1->getKey())
                ->and($sorted[1]['id'])->toBe($post2->getKey());
        });
    });

    describe('default trait behavior', function (): void {
        it('isSortable returns false by default on the trait', function (): void {
            // Test the trait directly via an anonymous class
            $instance = new class()
            {
                use \TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsSorting;
            };

            $reflection = new ReflectionMethod($instance, 'isSortable');

            expect($reflection->invoke($instance))->toBeFalse();
        });

        it('sortRows is a no-op by default on the trait', function (): void {
            $instance = new class()
            {
                use \TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsSorting;
            };

            // Should not throw - just a no-op
            $instance->sortRows(1, 5);

            // Verify instance is still valid (method did nothing)
            expect($instance)->toBeObject();
        });

        it('sortRows accepts string id by default on the trait', function (): void {
            $instance = new class()
            {
                use \TeamNiftyGmbH\DataTable\Traits\DataTables\SupportsSorting;
            };

            $instance->sortRows('uuid-test', 3);

            expect($instance)->toBeObject();
        });
    });
});
