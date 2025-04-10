<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Datatables\UserDataTable;
use Tests\Models\User;

class DataTableTest extends BrowserTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create a route for user details
        Route::get('/users/{id}', function ($id) {
            return "User details for user $id";
        })->name('users.show');

        // Create test users
        User::factory()->count(10)->create();
    }

    #[Test]
    public function it_can_click_row_actions(): void
    {
        Livewire::visit(UserDataTable::class)
            ->waitForLivewire()
            ->waitUntilMissingText('No data found')
            ->clickAtXpath('/html/body/div[4]/div/div[3]/div/table/tbody/tr[3]')
            ->waitForText('user detail view')
            ->assertSee('user detail view');
    }

    #[Test]
    public function it_can_open_sidebar_filters(): void
    {
        Livewire::visit(UserDataTable::class)
            ->waitForLivewire()
            ->waitUntilMissingText('No data found')
            ->click('button[x-on\:click="loadSidebar()"]')
            ->waitForLivewire()
            ->waitForText()
            ->assertSee('Add filter')
            ->click('@add-filter')
            ->waitForLivewire()
            ->click('@close-sidebar');
    }

    #[Test]
    public function it_can_paginate_results(): void
    {
        User::factory()->count(20)->create();

        Livewire::visit(UserDataTable::class)
            ->waitForLivewire()
            ->waitUntilMissingText('No data found')
            ->click('tfoot nav:nth-child(2)')
            ->waitForLivewire()
            ->click('@page-1')
            ->waitForLivewire();
    }

    #[Test]
    public function it_can_render_datatable_component(): void
    {
        Livewire::visit(UserDataTable::class)
            ->waitForLivewire()
            ->waitUntilMissingText('No data found')
            ->assertSee('Name')
            ->assertSee('Email')
            ->assertSee('Created At');
    }

    #[Test]
    public function it_can_search_data(): void
    {
        // Create a user with a known name
        User::factory()->create(['name' => 'SearchableUser']);

        Livewire::visit(UserDataTable::class)
            ->waitForLivewire()
            ->waitUntilMissingText('No data found')
                    // Type in the search box
            ->type('@search-input', 'SearchableUser')
            ->waitForLivewire()
            ->assertSee('SearchableUser')
                    // Clear the search
            ->clear('@search-input')
            ->waitForLivewire();
    }

    #[Test]
    public function it_can_select_rows(): void
    {
        Livewire::visit(UserDataTable::class)
            ->waitForLivewire()
            ->waitUntilMissingText('No data found')
                    // Select the first row
            ->click('@select-row-1')
            ->waitForLivewire()
                    // Select another row
            ->click('@select-row-2')
            ->waitForLivewire();
    }

    #[Test]
    public function it_can_sort_data(): void
    {
        Livewire::visit(UserDataTable::class)
            ->waitForLivewire()
            ->waitUntilMissingText('No data found')
                    // Click the name column header to sort by name
            ->click('@sort-name')
            ->waitForLivewire()
                    // Click again to reverse the sort order
            ->click('@sort-name')
            ->waitForLivewire();
    }
}
