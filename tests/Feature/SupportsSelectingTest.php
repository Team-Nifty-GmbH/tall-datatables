<?php

use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\NonExportablePostDataTable;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\SelectablePostDataTable;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('SupportsSelecting', function (): void {
    describe('isSelectable property', function (): void {
        it('defaults to false on base datatable', function (): void {
            $component = Livewire::test(NonExportablePostDataTable::class);

            expect($component->get('isSelectable'))->toBeFalse();
        });

        it('can be set to true', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            expect($component->get('isSelectable'))->toBeTrue();
        });
    });

    describe('selected property', function (): void {
        it('defaults to empty array', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            expect($component->get('selected'))->toBe([]);
        });
    });

    describe('selectedIndex property', function (): void {
        it('defaults to empty array', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            expect($component->get('selectedIndex'))->toBe([]);
        });
    });

    describe('wildcardSelectExcluded property', function (): void {
        it('defaults to empty array', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            expect($component->get('wildcardSelectExcluded'))->toBe([]);
        });
    });

    describe('toggleSelected', function (): void {
        it('adds an item to selected', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->call('toggleSelected', $post->getKey());

            expect($component->get('selected'))->toContain($post->getKey());
        });

        it('removes an item from selected when already present', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->call('toggleSelected', $post->getKey())
                ->call('toggleSelected', $post->getKey());

            expect($component->get('selected'))->not->toContain($post->getKey());
        });

        it('adds item to wildcardSelectExcluded when deselecting', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->call('toggleSelected', $post->getKey())
                ->call('toggleSelected', $post->getKey());

            expect($component->get('wildcardSelectExcluded'))->toContain($post->getKey());
        });

        it('can select multiple items', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);
            $post3 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->call('toggleSelected', $post1->getKey())
                ->call('toggleSelected', $post2->getKey())
                ->call('toggleSelected', $post3->getKey());

            expect($component->get('selected'))->toHaveCount(3)
                ->toContain($post1->getKey())
                ->toContain($post2->getKey())
                ->toContain($post3->getKey());
        });

        it('re-indexes selected array after removal', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);
            $post3 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->call('toggleSelected', $post1->getKey())
                ->call('toggleSelected', $post2->getKey())
                ->call('toggleSelected', $post3->getKey())
                ->call('toggleSelected', $post2->getKey());

            $selected = $component->get('selected');

            // Should be re-indexed (0, 1) not (0, 2)
            expect(array_keys($selected))->toBe([0, 1]);
            expect($selected)->toContain($post1->getKey())
                ->toContain($post3->getKey());
        });

        it('works with string values', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class)
                ->call('toggleSelected', 'string-id-1')
                ->call('toggleSelected', 'string-id-2');

            expect($component->get('selected'))->toContain('string-id-1')
                ->toContain('string-id-2');
        });
    });

    describe('getSelectValue', function (): void {
        it('returns default based on modelKeyName', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            $value = $component->instance()->getSelectValue();

            expect($value)->toBe('record.id');
        });
    });

    describe('getSelectAttributes', function (): void {
        it('returns a ComponentAttributeBag', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            $attributes = $component->instance()->getSelectAttributes();

            expect($attributes)->toBeInstanceOf(ComponentAttributeBag::class);
        });

        it('returns empty bag by default', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            $attributes = $component->instance()->getSelectAttributes();

            expect($attributes->getAttributes())->toBeEmpty();
        });
    });

    describe('getSelectedActions', function (): void {
        it('returns empty array by default', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'getSelectedActions');
            $actions = $reflection->invoke($component->instance());

            expect($actions)->toBe([]);
        });
    });

    describe('getSelectedModels', function (): void {
        it('returns empty collection when nothing selected', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'getSelectedModels');
            $models = $reflection->invoke($component->instance());

            expect($models)->toBeEmpty();
        });

        it('returns models for selected ids', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);
            createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->call('toggleSelected', $post1->getKey())
                ->call('toggleSelected', $post2->getKey());

            $reflection = new ReflectionMethod($component->instance(), 'getSelectedModels');
            $models = $reflection->invoke($component->instance());

            expect($models)->toHaveCount(2);
            expect($models->pluck('id')->toArray())->toContain($post1->getKey())
                ->toContain($post2->getKey());
        });
    });

    describe('getSelectedModelsQuery', function (): void {
        it('returns a query builder', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'getSelectedModelsQuery');
            $query = $reflection->invoke($component->instance());

            expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        });

        it('scopes query to selected values', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);
            $post3 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->call('toggleSelected', $post1->getKey())
                ->call('toggleSelected', $post3->getKey());

            $reflection = new ReflectionMethod($component->instance(), 'getSelectedModelsQuery');
            $count = $reflection->invoke($component->instance())->count();

            expect($count)->toBe(2);
        });
    });

    describe('getSelectedValues', function (): void {
        it('returns selected array when no wildcard', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->call('toggleSelected', $post1->getKey())
                ->call('toggleSelected', $post2->getKey());

            $reflection = new ReflectionMethod($component->instance(), 'getSelectedValues');
            $values = $reflection->invoke($component->instance());

            expect($values)->toHaveCount(2)
                ->toContain($post1->getKey())
                ->toContain($post2->getKey());
        });

        it('returns all ids minus excluded when wildcard is selected', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);
            $post3 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->set('selected', ['*'])
                ->set('wildcardSelectExcluded', [$post2->getKey()])
                ->call('loadData');

            $reflection = new ReflectionMethod($component->instance(), 'getSelectedValues');
            $values = $reflection->invoke($component->instance());

            expect($values)->toContain($post1->getKey())
                ->toContain($post3->getKey())
                ->not->toContain($post2->getKey());
        });

        it('returns all ids when wildcard selected with no exclusions', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(SelectablePostDataTable::class)
                ->set('selected', ['*'])
                ->call('loadData');

            $reflection = new ReflectionMethod($component->instance(), 'getSelectedValues');
            $values = $reflection->invoke($component->instance());

            expect($values)->toContain($post1->getKey())
                ->toContain($post2->getKey());
        });
    });

    describe('wildcard select behavior in loadData', function (): void {
        it('expands wildcard to actual ids on loadData', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(PostDataTable::class)
                ->set('selected', ['*'])
                ->call('loadData');

            $selected = $component->get('selected');

            // Should contain '*' plus the individual IDs
            expect($selected)->toContain('*')
                ->toContain($post1->getKey())
                ->toContain($post2->getKey());
        });

        it('excludes wildcardSelectExcluded from expanded wildcard', function (): void {
            $post1 = createTestPost(['user_id' => $this->user->getKey()]);
            $post2 = createTestPost(['user_id' => $this->user->getKey()]);
            $post3 = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(PostDataTable::class)
                ->set('selected', ['*'])
                ->set('wildcardSelectExcluded', [$post2->getKey()])
                ->call('loadData');

            $selected = $component->get('selected');

            expect($selected)->toContain('*')
                ->toContain($post1->getKey())
                ->toContain($post3->getKey())
                ->not->toContain($post2->getKey());
        });
    });

    describe('selection resets', function (): void {
        it('resets selected on startSearch', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(PostDataTable::class)
                ->call('toggleSelected', $post->getKey())
                ->call('startSearch');

            expect($component->get('selected'))->toBe([]);
        });
    });

    describe('config includes selectable', function (): void {
        it('includes selectable in config when enabled', function (): void {
            $component = Livewire::test(SelectablePostDataTable::class);

            $config = $component->instance()->getConfig();

            expect($config)->toHaveKey('selectable')
                ->and($config['selectable'])->toBeTrue();
        });

        it('includes selectable as false when disabled', function (): void {
            $component = Livewire::test(NonExportablePostDataTable::class);

            $config = $component->instance()->getConfig();

            expect($config)->toHaveKey('selectable')
                ->and($config['selectable'])->toBeFalse();
        });
    });
});
