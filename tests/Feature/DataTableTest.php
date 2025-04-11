<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Datatables\UserDataTable;
use Tests\Models\User;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    use WithFaker;

    #[Test]
    public function it_can_display_and_render_data(): void
    {
        // Create some test users
        User::factory()->count(5)->create();

        Livewire::test(UserDataTable::class)
            ->call('loadData')
            ->assertSet('initialized', true)
            ->assertViewHas('modelName', 'User')
            ->assertViewHas('searchable', true)
            ->assertViewHas('isSelectable', true);
    }

    #[Test]
    public function it_can_filter_data(): void
    {
        // Create test users
        User::factory()->create(['email' => 'test@example.com']);
        User::factory()->create(['email' => 'another@example.com']);

        $component = Livewire::test(UserDataTable::class)
            ->call('loadData');

        // Add a filter for email like test@
        $component
            ->set('userFilters', [
                [
                    [
                        'column' => 'email',
                        'operator' => 'like',
                        'value' => '%test%',
                        'relation' => '',
                    ],
                ],
            ])
            ->call('applyUserFilters');

        // Verify filter is applied
        $this->assertEquals('like', $component->get('userFilters.0.0.operator'));
        $this->assertEquals('%test%', $component->get('userFilters.0.0.value'));
    }

    #[Test]
    public function it_can_mount_datatable_component(): void
    {
        Livewire::test(UserDataTable::class)
            ->assertSuccessful()
            ->assertViewIs('tall-datatables::livewire.data-table');
    }

    #[Test]
    public function it_can_paginate_results(): void
    {
        // Create more users than default per page
        User::factory()->count(20)->create();

        Livewire::test(UserDataTable::class)
            ->call('loadData')
            ->assertSet('perPage', 15) // Default value
            ->assertSet('page', '1')
            ->call('gotoPage', 2)
            ->assertSet('page', 2);
    }

    #[Test]
    public function it_can_select_rows(): void
    {
        // Create users
        $users = User::factory()->count(3)->create();

        $component = Livewire::test(UserDataTable::class)
            ->call('loadData');

        // Select specific rows
        $component
            ->set('selected', [$users[0]->id, $users[1]->id])
            ->assertSet('selected', [$users[0]->id, $users[1]->id]);
    }

    #[Test]
    public function it_can_sort_data(): void
    {
        // Create test users with different creation times
        User::factory()->create(['name' => 'Adam']);
        User::factory()->create(['name' => 'Zach']);
        User::factory()->create(['name' => 'Bob']);

        $component = Livewire::test(UserDataTable::class)
            ->call('loadData')
            ->assertSet('initialized', true);

        // Sort by name ascending
        $component->call('sortTable', 'name')
            ->assertSet('userOrderBy', 'name')
            ->assertSet('userOrderAsc', true);

        // Toggle sort direction
        $component->call('sortTable', 'name')
            ->assertSet('userOrderBy', 'name')
            ->assertSet('userOrderAsc', false);
    }

    #[Test]
    public function it_has_working_table_and_row_actions(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Test the table actions and row actions are properly defined
        $component = Livewire::test(UserDataTable::class);

        $tableActions = $component->instance()->getTableActions();
        $rowActions = $component->instance()->getRowActions();

        // Assert table actions exist
        $this->assertNotEmpty($tableActions);
        $this->assertCount(1, $tableActions);
        $this->assertEquals('Create User', $tableActions[0]->text);

        // Assert row actions exist
        $this->assertNotEmpty($rowActions);
        $this->assertCount(2, $rowActions);
        $this->assertEquals('Edit', $rowActions[0]->text);
        $this->assertEquals('Delete', $rowActions[1]->text);

        // Test the actions methods can be called
        $component->call('edit', $user->id);
        $component->call('delete', $user->id);
    }

    #[Test]
    public function it_shows_correct_formatters(): void
    {
        User::factory()->create();

        $component = Livewire::test(UserDataTable::class)
            ->call('loadData');

        $formatters = $component->instance()->getFormatters();

        $this->assertEquals('datetime', $formatters['email_verified_at']);
        $this->assertEquals('date', $formatters['created_at']);
    }
}
