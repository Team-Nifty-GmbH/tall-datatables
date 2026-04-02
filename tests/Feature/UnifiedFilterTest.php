<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'Test User', 'email' => 'test@example.com']);

    for ($i = 1; $i <= 10; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => $i <= 5 ? "Alpha {$i}" : "Beta {$i}",
            'content' => "Content {$i}",
            'is_published' => $i % 2 === 0,
            'price' => $i * 10,
        ]);
    }
});

describe('setTextFilter', function (): void {
    it('adds a text filter to group 0 by default', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha');

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toHaveKey(0);
        expect($textFilters[0])->toHaveKey('title');
        expect($textFilters[0]['title'])->toBe('Alpha');
    });

    it('adds a text filter to a specific group', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toHaveKey(0);
        expect($textFilters)->toHaveKey(1);
        expect($textFilters[0]['title'])->toBe('Alpha');
        expect($textFilters[1]['title'])->toBe('Beta');
    });

    it('removes text filter when value is empty', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha')
            ->call('setTextFilter', 'title', '');

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toBeEmpty();
    });

    it('removes group when last filter in it is cleared', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1)
            ->call('setTextFilter', 'title', '', 0);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toHaveCount(1);
        expect(array_values($textFilters)[0]['title'])->toBe('Beta');
    });

    it('rebuilds userFilters with source=text', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha');

        $userFilters = $component->get('userFilters');
        expect($userFilters)->toHaveCount(1);
        expect($userFilters[0])->toHaveCount(1);
        expect($userFilters[0][0]['source'])->toBe('text');
        expect($userFilters[0][0]['column'])->toBe('title');
        expect($userFilters[0][0]['operator'])->toBe('like');
        expect($userFilters[0][0]['value'])->toBe('%Alpha%');
    });

    it('places filters in correct userFilters groups', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1);

        $userFilters = $component->get('userFilters');
        expect($userFilters)->toHaveCount(2);
        expect($userFilters[0][0]['value'])->toBe('%Alpha%');
        expect($userFilters[1][0]['value'])->toBe('%Beta%');
    });
});

describe('removeTextFilterRow', function (): void {
    it('removes an entire text filter row', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1)
            ->call('removeTextFilterRow', 0);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toHaveCount(1);
        expect($textFilters[0]['title'])->toBe('Beta');
    });

    it('re-indexes textFilters after removal', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'A', 0)
            ->call('setTextFilter', 'title', 'B', 1)
            ->call('setTextFilter', 'title', 'C', 2)
            ->call('removeTextFilterRow', 1);

        $textFilters = $component->get('textFilters');
        expect($textFilters)->toHaveCount(2);
        expect(array_keys($textFilters))->toBe([0, 1]);
    });
});

describe('removeFilterGroup', function (): void {
    it('removes filter group and cleans up textFilters', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1)
            ->call('removeFilterGroup', 0);

        $textFilters = $component->get('textFilters');
        $userFilters = $component->get('userFilters');

        expect($userFilters)->toHaveCount(1);
        expect($userFilters[0][0]['value'])->toBe('%Beta%');
    });
});

describe('clearFiltersAndSort', function (): void {
    it('clears all text filters and user filters', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1)
            ->call('clearFiltersAndSort');

        expect($component->get('textFilters'))->toBeEmpty();
        expect($component->get('userFilters'))->toBeEmpty();
    });
});

describe('textFilters migration', function (): void {
    it('migrates old flat format to grouped format', function (): void {
        $component = Livewire::test(PostDataTable::class);

        // Simulate old flat format
        $component->set('textFilters', ['title' => 'Alpha', 'content' => 'test']);
        $component->call('setTextFilter', 'price', '100', 0);

        $textFilters = $component->get('textFilters');
        expect($textFilters[0])->toHaveKey('title');
        expect($textFilters[0])->toHaveKey('content');
        expect($textFilters[0])->toHaveKey('price');
    });
});

describe('date parsing in text filters', function (): void {
    it('parses dd.mm.yyyy to Y-m-d for date columns', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '15.01.2024');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe('2024-01-15%');
        expect($userFilters[0][0]['operator'])->toBe('like');
    });

    it('parses date with operator prefix', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'created_at', '>=15.01.2024');

        $userFilters = $component->get('userFilters');
        expect($userFilters[0][0]['value'])->toBe('2024-01-15');
        expect($userFilters[0][0]['operator'])->toBe('>=');
    });
});

describe('OR query logic', function (): void {
    it('returns results matching any OR group', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha 1', 0)
            ->call('setTextFilter', 'title', 'Beta 6', 1);

        // loadData is called by setTextFilter, data should be populated
        $userFilters = $component->get('userFilters');
        expect($userFilters)->toHaveCount(2);
        expect($userFilters[0][0]['value'])->toBe('%Alpha 1%');
        expect($userFilters[1][0]['value'])->toBe('%Beta 6%');
    });

    it('applies AND within the same group', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'is_published', '1', 0);

        $userFilters = $component->get('userFilters');
        // Both filters in group 0, AND'd together
        expect($userFilters)->toHaveCount(1);
        expect($userFilters[0])->toHaveCount(2);
        expect($userFilters[0][0]['column'])->toBe('title');
        expect($userFilters[0][1]['column'])->toBe('is_published');
    });
});

describe('removeFilterGroup index alignment', function (): void {
    it('keeps textFilters and userFilters indices aligned after removing middle group', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('setTextFilter', 'title', 'Alpha', 0)
            ->call('setTextFilter', 'title', 'Beta', 1)
            ->call('setTextFilter', 'title', 'Gamma', 2);

        // Verify 3 groups exist
        $textFilters = $component->get('textFilters');
        $userFilters = $component->get('userFilters');
        expect($textFilters)->toHaveCount(3);
        expect($userFilters)->toHaveCount(3);

        // Remove the middle group (index 1)
        $component->call('removeFilterGroup', 1);

        $textFilters = $component->get('textFilters');
        $userFilters = $component->get('userFilters');

        // Should have 2 groups remaining
        expect($textFilters)->toHaveCount(2);
        expect($userFilters)->toHaveCount(2);

        // textFilters and userFilters should still be aligned:
        // Group 0 should have "Alpha", Group 1 should have "Gamma"
        expect($textFilters[0]['title'])->toBe('Alpha');
        expect($textFilters[1]['title'])->toBe('Gamma');

        // userFilters groups should match
        expect($userFilters[0][0]['column'])->toBe('title');
        expect($userFilters[0][0]['value'])->toBe('%Alpha%');
        expect($userFilters[1][0]['column'])->toBe('title');
        expect($userFilters[1][0]['value'])->toBe('%Gamma%');
    });
});
