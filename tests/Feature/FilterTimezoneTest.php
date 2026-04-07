<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\TimezonePostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Filter timezone conversion', function (): void {
    it('converts datetime filter value from display timezone to database timezone', function (): void {
        // Post created at 14:30 UTC
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Timezone Test',
            'created_at' => '2024-01-15 14:30:00',
        ]);

        // User in Europe/Berlin filters for >= 15:30 Berlin time (= 14:30 UTC)
        $component = Livewire::test(TimezonePostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => '15.01.2024 15:30']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('excludes records outside display timezone range', function (): void {
        // Post created at 13:00 UTC = 14:00 Berlin
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Too Early',
            'created_at' => '2024-01-15 13:00:00',
        ]);

        // User in Berlin filters for >= 15:00 Berlin time (= 14:00 UTC)
        // Post at 14:00 Berlin should NOT match >= 15:00 Berlin
        $component = Livewire::test(TimezonePostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => '15.01.2024 15:00']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(0);
    });

    it('converts date-only filter with timezone (date boundary shift)', function (): void {
        // Post created at 23:30 UTC on Jan 15 = 00:30 Berlin on Jan 16
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Late Night UTC',
            'created_at' => '2024-01-15 23:30:00',
        ]);

        // User in Berlin filters for Jan 16 — should find the post because 23:30 UTC = Jan 16 in Berlin
        $component = Livewire::test(TimezonePostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => '16.01.2024']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('handles DST correctly in summer', function (): void {
        // Post created at 14:00 UTC on July 15 = 16:00 Berlin (CEST, UTC+2)
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Summer Post',
            'created_at' => '2024-07-15 14:00:00',
        ]);

        // User in Berlin filters for >= 16:00 Berlin (= 14:00 UTC in summer)
        $component = Livewire::test(TimezonePostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => '15.07.2024 16:00']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });

    it('does not convert when timezones are the same', function (): void {
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Same TZ',
            'created_at' => '2024-01-15 14:30:00',
        ]);

        // PostDataTable has same db and display timezone (both config('app.timezone'))
        $component = Livewire::test(Tests\Fixtures\Livewire\PostDataTable::class);
        $component->set('userFilters', [
            [['column' => 'created_at', 'operator' => '>=', 'value' => '15.01.2024 14:30']],
        ]);
        $component->call('loadData');

        $data = $component->instance()->getDataForTesting();
        expect($data['total'])->toBe(1);
    });
});
