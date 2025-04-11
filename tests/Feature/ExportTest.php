<?php

namespace Tests\Feature;

use Exception;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Datatables\UserDataTable;
use Tests\Models\User;
use Tests\TestCase;

class ExportTest extends TestCase
{
    #[Test]
    public function it_can_export_data(): void
    {
        // This is a simplified test as we can't actually test the Excel export easily
        // But we can test that the method doesn't throw exceptions

        User::factory()->count(5)->create();

        $component = Livewire::test(UserDataTable::class)
            ->call('loadData');

        // The export method returns a download response,
        // but in tests we just want to make sure it doesn't throw an exception
        try {
            $component->call('export', ['id', 'name', 'email']);
            $this->assertTrue(true); // If we got here, no exception was thrown
        } catch (Exception $e) {
            $this->fail('Exception thrown when trying to export: ' . $e->getMessage());
        }
    }

    #[Test]
    public function it_can_get_exportable_columns(): void
    {
        // Create some test users
        User::factory()->count(3)->create();

        // Test the component
        $component = Livewire::test(UserDataTable::class)
            ->call('loadData');

        // Call the method to get exportable columns
        $columns = $component->instance()->getExportableColumns();

        // Verify the columns include our model attributes
        $this->assertContains('id', $columns);
        $this->assertContains('name', $columns);
        $this->assertContains('email', $columns);
    }
}
