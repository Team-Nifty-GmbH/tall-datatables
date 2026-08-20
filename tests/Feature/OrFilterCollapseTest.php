<?php

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Models\Tag;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('OR conditions on the same relation column', function (): void {
    it('folds them into a single subquery', function (): void {
        foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $name) {
            Tag::create(['name' => $name]);
        }

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'tags.name', 'operator' => '=', 'value' => 'Alpha']],
                [['column' => 'tags.name', 'operator' => '=', 'value' => 'Beta']],
                [['column' => 'tags.name', 'operator' => '=', 'value' => 'Gamma']],
            ])
            ->call('loadData');

        $select = collect($queries)->first(fn (string $sql) => str_contains($sql, 'exists'));

        expect($select)->not->toBeNull()
            ->and(substr_count($select, 'exists'))->toBe(1);
    });

    it('still matches every row that any of the values matches', function (): void {
        $alpha = Tag::create(['name' => 'Alpha']);
        $beta = Tag::create(['name' => 'Beta']);
        $gamma = Tag::create(['name' => 'Gamma']);
        $delta = Tag::create(['name' => 'Delta']);

        $withAlpha = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Alpha']);
        $withAlpha->tags()->attach($alpha);

        $withBeta = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Beta']);
        $withBeta->tags()->attach($beta);

        $withGamma = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Gamma']);
        $withGamma->tags()->attach($gamma);

        $withDelta = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Delta']);
        $withDelta->tags()->attach($delta);

        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has None']);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'tags.name', 'operator' => '=', 'value' => 'Alpha']],
                [['column' => 'tags.name', 'operator' => '=', 'value' => 'Beta']],
                [['column' => 'tags.name', 'operator' => '=', 'value' => 'Gamma']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->all();

        expect($data['total'])->toBe(3)
            ->and($titles)->toContain('Has Alpha')
            ->and($titles)->toContain('Has Beta')
            ->and($titles)->toContain('Has Gamma')
            ->and($titles)->not->toContain('Has Delta')
            ->and($titles)->not->toContain('Has None');
    });

    it('leaves a negated condition in its own subquery', function (): void {
        $alpha = Tag::create(['name' => 'Alpha']);
        $beta = Tag::create(['name' => 'Beta']);

        $withAlpha = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Alpha']);
        $withAlpha->tags()->attach($alpha);

        $withBeta = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Beta']);
        $withBeta->tags()->attach($beta);

        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has None']);

        // "not Alpha" OR "not Beta" is not the same as "not in (Alpha, Beta)",
        // so these two must stay two subqueries.
        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'tags.name', 'operator' => '!=', 'value' => 'Alpha']],
                [['column' => 'tags.name', 'operator' => '!=', 'value' => 'Beta']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->all();

        expect($data['total'])->toBe(3)
            ->and($titles)->toContain('Has Alpha')
            ->and($titles)->toContain('Has Beta')
            ->and($titles)->toContain('Has None');
    });
});
