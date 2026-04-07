<?php

use Carbon\Carbon;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Quarter calculation support', function (): void {
    it('applies startOfQuarter calculation in filters', function (): void {
        Carbon::setTestNow(Carbon::create(2026, 5, 15));

        $inQuarter = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'In Quarter',
            'created_at' => '2026-04-10 12:00:00',
        ]);

        $beforeQuarter = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Before Quarter',
            'created_at' => '2026-03-15 12:00:00',
        ]);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title', 'created_at'])
            ->set('userFilters', [
                [[
                    'column' => 'created_at',
                    'operator' => '>=',
                    'value' => [['calculation' => [
                        'operator' => '-',
                        'value' => 0,
                        'unit' => 'months',
                        'is_start_of' => '1',
                        'start_of' => 'quarter',
                    ]]],
                ]],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->toArray();

        expect($titles)->toContain('In Quarter')
            ->and($titles)->not->toContain('Before Quarter');

        Carbon::setTestNow();
    });

    it('applies endOfQuarter calculation in filters', function (): void {
        Carbon::setTestNow(Carbon::create(2026, 5, 15));

        $afterQuarter = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'After Quarter',
            'created_at' => '2026-07-01 12:00:00',
        ]);

        $inQuarter = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'In Quarter',
            'created_at' => '2026-06-30 23:00:00',
        ]);

        $component = Livewire::test(PostDataTable::class)
            ->set('enabledCols', ['title', 'created_at'])
            ->set('userFilters', [
                [[
                    'column' => 'created_at',
                    'operator' => '<=',
                    'value' => [['calculation' => [
                        'operator' => '+',
                        'value' => 0,
                        'unit' => 'months',
                        'is_start_of' => '0',
                        'start_of' => 'quarter',
                    ]]],
                ]],
            ])
            ->call('loadData');

        $data = $component->instance()->getDataForTesting();
        $titles = collect($data['data'])->pluck('title')->toArray();

        expect($titles)->toContain('In Quarter')
            ->and($titles)->not->toContain('After Quarter');

        Carbon::setTestNow();
    });
});
