<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Datatables\UserDataTable;

class ComponentRenderingTest extends BrowserTestCase
{
    protected function tearDown(): void
    {
        // Clean up
        if (File::exists(public_path('js/livewire.js'))) {
            File::delete(public_path('js/livewire.js'));
        }

        if (File::exists(public_path('build/assets/app.js'))) {
            File::delete(public_path('build/assets/app.js'));
        }

        parent::tearDown();
    }

    #[Test]
    public function it_can_handle_button_click_events(): void
    {
        Livewire::visit(UserDataTable::class)
            ->waitFor('#click-event-button')
            ->click('#click-event-button')
            ->pause(500)
            ->assertSee('Button clicked!');
    }

    #[Test]
    public function it_can_render_circle_button(): void
    {
        Livewire::visit(UserDataTable::class)
            ->waitFor('#circle-button')
            ->assertVisible('#circle-button');
    }

    #[Test]
    public function it_can_render_datatable_button(): void
    {
        Livewire::visit(UserDataTable::class)
            ->waitFor('#test-button')
            ->assertSee('Test Button')
            ->assertVisible('#test-button');
    }

    #[Test]
    public function it_can_toggle_conditional_button(): void
    {
        Livewire::visit(UserDataTable::class)
            ->waitFor('#toggle-condition-button')
            ->click('#toggle-condition-button')
            ->pause(500)
            ->assertVisible('#conditional-button')
            ->click('#toggle-condition-button')
            ->pause(500)
            ->assertMissing('#conditional-button');
    }
}
