<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Models\Tag;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Negation operators on to-many relation columns', function (): void {
    it('excludes rows that have a matching related record, including rows with no related records', function (): void {
        $reisebuero = Tag::create(['name' => 'Reisebüro']);
        $request = Tag::create(['name' => 'Kat-Anfrage']);

        $onlyReisebuero = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Only Reisebüro']);
        $onlyReisebuero->tags()->attach($reisebuero);

        $reisebueroAndOther = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Reisebüro And Other']);
        $reisebueroAndOther->tags()->attach([$reisebuero->getKey(), $request->getKey()]);

        $otherOnly = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Other Only']);
        $otherOnly->tags()->attach($request);

        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Tags']);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'tags.name', 'operator' => '!=', 'value' => 'Reisebüro']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->all();

        expect($data['total'])->toBe(2)
            ->and($titles)->toContain('Other Only')
            ->and($titles)->toContain('No Tags')
            ->and($titles)->not->toContain('Only Reisebüro')
            ->and($titles)->not->toContain('Reisebüro And Other');
    });

    it('excludes rows whose related record is in the negated list with the not in operator', function (): void {
        $reisebuero = Tag::create(['name' => 'Reisebüro']);
        $wholesale = Tag::create(['name' => 'Direktkunde']);
        $request = Tag::create(['name' => 'Kat-Anfrage']);

        $reise = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Reisebüro Post']);
        $reise->tags()->attach($reisebuero);

        $direkt = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Direktkunde Post']);
        $direkt->tags()->attach($wholesale);

        $keep = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Keep Post']);
        $keep->tags()->attach($request);

        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Tags']);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'tags.name', 'operator' => 'not in', 'value' => 'Reisebüro, Direktkunde']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->all();

        expect($data['total'])->toBe(2)
            ->and($titles)->toContain('Keep Post')
            ->and($titles)->toContain('No Tags')
            ->and($titles)->not->toContain('Reisebüro Post')
            ->and($titles)->not->toContain('Direktkunde Post');
    });

    it('excludes rows whose related record matches with the does not contain operator', function (): void {
        $reisebuero = Tag::create(['name' => 'Reisebüro Süd']);
        $request = Tag::create(['name' => 'Kat-Anfrage']);

        $reise = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Has Reisebüro']);
        $reise->tags()->attach($reisebuero);

        $keep = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Keep Post']);
        $keep->tags()->attach($request);

        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Tags']);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'tags.name', 'operator' => 'does not contain', 'value' => 'Reisebüro']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->all();

        expect($data['total'])->toBe(2)
            ->and($titles)->not->toContain('Has Reisebüro');
    });

    it('still uses whereHas for positive operators so only matching rows remain', function (): void {
        $reisebuero = Tag::create(['name' => 'Reisebüro']);
        $request = Tag::create(['name' => 'Kat-Anfrage']);

        $reise = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Reisebüro Post']);
        $reise->tags()->attach($reisebuero);

        $other = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Other Post']);
        $other->tags()->attach($request);

        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'No Tags']);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title'])
            ->set('userFilters', [
                [['column' => 'tags.name', 'operator' => '=', 'value' => 'Reisebüro']],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->all();

        expect($data['total'])->toBe(1)
            ->and($titles)->toContain('Reisebüro Post');
    });
});
