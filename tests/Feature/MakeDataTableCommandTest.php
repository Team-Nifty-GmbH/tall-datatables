<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MakeDataTableCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $testFile = app_path('Livewire/DataTables/TestDataTable.php');
        if (File::exists($testFile)) {
            File::delete($testFile);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_can_generate_datatable_class(): void
    {
        // Configure app path for livewire
        app('config')->set('livewire.class_namespace', 'App\\Livewire');
        app('config')->set('tall-datatables.data_table_namespace', 'App\\Livewire\\DataTables');

        // Make sure the directories exist
        File::ensureDirectoryExists(app_path('Livewire/DataTables'));

        // Run the command
        $this->artisan('make:data-table', [
            'name' => 'TestDataTable',
            'model' => 'Tests\\Models\\User',
            '--force' => true,
        ])
            ->assertExitCode(0);

        // Check if the file was created
        $this->assertTrue(File::exists(app_path('Livewire/DataTables/TestDataTable.php')));

        // Check file content
        $content = File::get(app_path('Livewire/DataTables/TestDataTable.php'));
        $this->assertStringContainsString('namespace App\\Livewire\\DataTables', $content);
        $this->assertStringContainsString('use Tests\\Models\\User', $content);
        $this->assertStringContainsString('protected string $model = User::class', $content);
    }
}
