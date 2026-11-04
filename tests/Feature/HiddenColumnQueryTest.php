<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\UserDataTable;
use Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->user = createTestUser(['password' => 'secret-a']);
    $this->other = createTestUser(['password' => 'secret-b']);
    $this->actingAs($this->user);

    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'First']);
    createTestPost(['user_id' => $this->other->getKey(), 'title' => 'Second']);
});

describe('hidden columns of the model', function (): void {
    it('are not offered as columns', function (): void {
        expect(Livewire::test(UserDataTable::class)->instance()->getAvailableCols())
            ->not->toContain('password');
    });

    it('are removed from columns, aggregates, sorting and grouping', function (): void {
        $component = Livewire::test(UserDataTable::class)
            ->set('enabledCols', ['name', 'password'])
            ->set('aggregatableCols', ['sum' => [], 'avg' => [], 'min' => [], 'max' => ['password'], 'count' => []])
            ->set('userOrderBy', 'password')
            ->set('userMultiSort', [['column' => 'password', 'asc' => true]])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();

        expect($component->get('enabledCols'))->toBe(['name'])
            ->and($component->get('userOrderBy'))->toBe('')
            ->and($component->get('userMultiSort'))->toBe([])
            ->and($data)->not->toHaveKey('aggregates.max.password')
            ->and(json_encode($data))->not->toContain('secret');
    });

    it('cannot be used to filter', function (): void {
        $component = Livewire::test(UserDataTable::class)
            ->set('userFilters', [
                [['column' => 'password', 'operator' => '=', 'value' => 'secret-a']],
                ['password' => 'secret-a'],
            ])
            ->call('loadData');

        expect($component->instance()->getDataForTesting()['total'])->toBe(User::query()->count())
            ->and(json_encode($component->get('userFilters')))->not->toContain('password');
    });

    it('cannot be grouped by', function (): void {
        $component = Livewire::test(UserDataTable::class)
            ->set('groupBy', 'password')
            ->call('loadData');

        expect($component->get('groupBy'))->toBeNull();
    });

    it('keep visible filters', function (): void {
        $component = Livewire::test(UserDataTable::class)
            ->set('userFilters', [
                [['column' => 'name', 'operator' => '=', 'value' => $this->other->name]],
            ])
            ->call('loadData');

        expect($component->instance()->getDataForTesting()['total'])->toBe(1);
    });
});

describe('hidden columns of a relation', function (): void {
    it('are not offered in the relation columns or the column tree', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->instance()->getRelationTableCols('user'))->not->toHaveKey('password')
            ->and(array_column($component->instance()->loadRelation(User::class, 'user')['cols'], 'col'))
            ->not->toContain('password')
            ->toContain('email');
    });

    it('cannot be used to filter or sort', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title', 'user.password'])
            ->set('userFilters', [
                [['column' => 'user.password', 'operator' => '=', 'value' => 'secret-a']],
            ])
            ->set('userOrderBy', 'user.password')
            ->call('loadData');

        expect($component->instance()->getDataForTesting()['total'])->toBe(2)
            ->and($component->get('enabledCols'))->toBe(['title'])
            ->and($component->get('userOrderBy'))->toBe('');
    });
});
