<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Enum/state filter negation', function (): void {
    describe('parseTextFilterValue with valueList columns', function (): void {
        it('uses = operator for regular valueList values', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            // Simulate filterValueLists being populated for is_published (boolean)
            $instance = $component->instance();
            $instance->filterValueLists['is_published'] = [
                ['value' => 1, 'label' => 'Yes'],
                ['value' => 0, 'label' => 'No'],
            ];

            $reflection = new ReflectionMethod($instance, 'parseTextFilterValue');
            $parsed = $reflection->invoke($instance, '1', 'is_published');

            expect($parsed['operator'])->toBe('=')
                ->and($parsed['value'])->toBe('1');
        });

        it('uses != operator when valueList value is prefixed with !', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $instance = $component->instance();
            $instance->filterValueLists['is_published'] = [
                ['value' => 1, 'label' => 'Yes'],
                ['value' => 0, 'label' => 'No'],
            ];

            $reflection = new ReflectionMethod($instance, 'parseTextFilterValue');
            $parsed = $reflection->invoke($instance, '!1', 'is_published');

            expect($parsed['operator'])->toBe('!=')
                ->and($parsed['value'])->toBe('1');
        });

        it('uses != operator for string enum values prefixed with !', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $instance = $component->instance();
            $instance->filterValueLists['status'] = [
                ['value' => 'open', 'label' => 'Open'],
                ['value' => 'closed', 'label' => 'Closed'],
                ['value' => 'pending', 'label' => 'Pending'],
            ];

            $reflection = new ReflectionMethod($instance, 'parseTextFilterValue');
            $parsed = $reflection->invoke($instance, '!open', 'status');

            expect($parsed['operator'])->toBe('!=')
                ->and($parsed['value'])->toBe('open');
        });
    });

    describe('integration with setTextFilter', function (): void {
        it('applies negated enum filter via setTextFilter', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Published', 'is_published' => true]);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Draft', 'is_published' => false]);

            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $instance = $component->instance();
            $instance->filterValueLists['is_published'] = [
                ['value' => 1, 'label' => 'Yes'],
                ['value' => 0, 'label' => 'No'],
            ];

            // Negate: filter for NOT published (!=1)
            $component->call('setTextFilter', 'is_published', '!1', 0);

            $data = $component->instance()->getDataForTesting();
            expect($data['total'])->toBe(1);
            expect($data['data'][0]['title'])->toBe('Draft');
        });

        it('applies regular enum filter via setTextFilter', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Published', 'is_published' => true]);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Draft', 'is_published' => false]);

            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $instance = $component->instance();
            $instance->filterValueLists['is_published'] = [
                ['value' => 1, 'label' => 'Yes'],
                ['value' => 0, 'label' => 'No'],
            ];

            // Regular: filter for published (=1)
            $component->call('setTextFilter', 'is_published', '1', 0);

            $data = $component->instance()->getDataForTesting();
            expect($data['total'])->toBe(1);
            expect($data['data'][0]['title'])->toBe('Published');
        });
    });
});
