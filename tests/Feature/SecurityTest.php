<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('sortTable SQL injection prevention', function (): void {
    it('rejects SQL injection payload in sortTable', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('sortTable', '(SELECT 1)');

        expect($component->get('userOrderBy'))->toBe('');
    });

    it('rejects SQL expression with semicolon in sortTable', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('sortTable', '1; DROP TABLE posts; --');

        expect($component->get('userOrderBy'))->toBe('');
    });

    it('allows valid column name in sortTable', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('sortTable', 'title');

        expect($component->get('userOrderBy'))->toBe('title');
    });

    it('allows valid column from sortable list in sortTable', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('sortTable', 'created_at');

        expect($component->get('userOrderBy'))->toBe('created_at');
    });
});

describe('setGroupBy SQL injection prevention', function (): void {
    it('rejects SQL injection payload in setGroupBy', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', '(SELECT 1)');

        expect($component->get('groupBy'))->toBeNull();
    });

    it('rejects SQL expression with semicolon in setGroupBy', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', '1; DROP TABLE posts; --');

        expect($component->get('groupBy'))->toBeNull();
    });

    it('allows valid column name in setGroupBy', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');

        expect($component->get('groupBy'))->toBe('is_published');
    });

    it('allows null to clear groupBy', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $component->call('setGroupBy', 'is_published');
        expect($component->get('groupBy'))->toBe('is_published');

        $component->call('setGroupBy', null);
        expect($component->get('groupBy'))->toBeNull();
    });
});
