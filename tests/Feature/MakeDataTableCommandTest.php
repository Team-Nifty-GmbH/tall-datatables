<?php

use Illuminate\Support\Facades\File;
use TeamNiftyGmbH\DataTable\Commands\MakeDataTableCommand;

describe('MakeDataTableCommand', function (): void {
    it('is registered as an artisan command', function (): void {
        $commands = array_keys(Artisan::all());

        expect($commands)->toContain('make:data-table');
    });

    it('has correct command signature', function (): void {
        $command = app()->make(MakeDataTableCommand::class);

        expect($command->getName())->toBe('make:data-table');
    });

    it('has a description', function (): void {
        $command = app()->make(MakeDataTableCommand::class);

        expect($command->getDescription())
            ->toBe('Create a new Livewire DataTable component');
    });

    it('accepts name argument', function (): void {
        $command = app()->make(MakeDataTableCommand::class);
        $definition = $command->getDefinition();

        expect($definition->hasArgument('name'))->toBeTrue();
    });

    it('accepts model argument', function (): void {
        $command = app()->make(MakeDataTableCommand::class);
        $definition = $command->getDefinition();

        expect($definition->hasArgument('model'))->toBeTrue();
    });

    it('accepts force option', function (): void {
        $command = app()->make(MakeDataTableCommand::class);
        $definition = $command->getDefinition();

        expect($definition->hasOption('force'))->toBeTrue();
    });

    it('accepts stub option', function (): void {
        $command = app()->make(MakeDataTableCommand::class);
        $definition = $command->getDefinition();

        expect($definition->hasOption('stub'))->toBeTrue();
    });
});

describe('MakeDataTableCommand stub file', function (): void {
    it('has a default stub file', function (): void {
        $stubPath = dirname(__DIR__, 2) . '/stubs/livewire.data-table.stub';

        expect(File::exists($stubPath))->toBeTrue();
    });

    it('stub contains DataTable base class extension', function (): void {
        $stubPath = dirname(__DIR__, 2) . '/stubs/livewire.data-table.stub';
        $contents = File::get($stubPath);

        expect($contents)->toContain('extends DataTable');
    });

    it('stub contains model placeholder', function (): void {
        $stubPath = dirname(__DIR__, 2) . '/stubs/livewire.data-table.stub';
        $contents = File::get($stubPath);

        expect($contents)->toContain('[model]');
    });

    it('stub contains namespace placeholder', function (): void {
        $stubPath = dirname(__DIR__, 2) . '/stubs/livewire.data-table.stub';
        $contents = File::get($stubPath);

        expect($contents)->toContain('[namespace]');
    });

    it('stub contains class name placeholder', function (): void {
        $stubPath = dirname(__DIR__, 2) . '/stubs/livewire.data-table.stub';
        $contents = File::get($stubPath);

        expect($contents)->toContain('[class]');
    });

    it('stub contains model import placeholder', function (): void {
        $stubPath = dirname(__DIR__, 2) . '/stubs/livewire.data-table.stub';
        $contents = File::get($stubPath);

        expect($contents)->toContain('[model_import]');
    });

    it('stub imports DataTable base class', function (): void {
        $stubPath = dirname(__DIR__, 2) . '/stubs/livewire.data-table.stub';
        $contents = File::get($stubPath);

        expect($contents)->toContain('use TeamNiftyGmbH\DataTable\DataTable;');
    });

    it('stub includes mount method', function (): void {
        $stubPath = dirname(__DIR__, 2) . '/stubs/livewire.data-table.stub';
        $contents = File::get($stubPath);

        expect($contents)->toContain('public function mount()');
    });
});

describe('MakeDataTableCommand execution', function (): void {
    it('creates a datatable class file', function (): void {
        $outputPath = app_path('Livewire/TestPostDataTable.php');

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'TestPostDataTable',
            'model' => Tests\Fixtures\Models\Post::class,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath))->toBeTrue();

        $contents = File::get($outputPath);

        expect($contents)->toContain('extends DataTable')
            ->toContain('Post');

        File::delete($outputPath);
    });

    it('does not overwrite existing file without force flag', function (): void {
        $outputPath = app_path('Livewire/ExistingDataTable.php');

        if (! File::isDirectory(dirname($outputPath))) {
            File::makeDirectory(dirname($outputPath), 0777, true, true);
        }

        File::put($outputPath, '<?php // existing');

        $this->artisan('make:data-table', [
            'name' => 'ExistingDataTable',
            'model' => Tests\Fixtures\Models\Post::class,
        ])->assertSuccessful();

        $contents = File::get($outputPath);

        expect($contents)->toBe('<?php // existing');

        File::delete($outputPath);
    });

    it('overwrites existing file with force flag', function (): void {
        $outputPath = app_path('Livewire/ForceDataTable.php');

        if (! File::isDirectory(dirname($outputPath))) {
            File::makeDirectory(dirname($outputPath), 0777, true, true);
        }

        File::put($outputPath, '<?php // old');

        $this->artisan('make:data-table', [
            'name' => 'ForceDataTable',
            'model' => Tests\Fixtures\Models\Post::class,
            '--force' => true,
        ])->assertSuccessful();

        $contents = File::get($outputPath);

        expect($contents)->toContain('extends DataTable');

        File::delete($outputPath);
    });

    it('uses model class basename as argument', function (): void {
        $outputPath = app_path('Livewire/ModelBaseDataTable.php');

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'ModelBaseDataTable',
            'model' => Tests\Fixtures\Models\Post::class,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath))->toBeTrue();

        $contents = File::get($outputPath);

        expect($contents)->toContain('Post');

        File::delete($outputPath);
    });

    it('rejects reserved class names', function (): void {
        $this->artisan('make:data-table', [
            'name' => 'Parent',
            'model' => Tests\Fixtures\Models\Post::class,
        ])->assertFailed();
    });

    it('rejects all reserved class names', function (string $reservedName): void {
        $this->artisan('make:data-table', [
            'name' => $reservedName,
            'model' => Tests\Fixtures\Models\Post::class,
        ])->assertFailed();
    })->with(['parent', 'component', 'interface', 'abstract', 'class', 'static', 'self']);

    it('rejects reserved names case-insensitively', function (): void {
        $this->artisan('make:data-table', [
            'name' => 'ABSTRACT',
            'model' => Tests\Fixtures\Models\Post::class,
        ])->assertFailed();
    });

    it('creates class in subdirectory with dot notation', function (): void {
        $outputPath = app_path('Livewire/Admin/PostTable.php');

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'Admin.PostTable',
            'model' => Tests\Fixtures\Models\Post::class,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath))->toBeTrue();

        $contents = File::get($outputPath);

        expect($contents)
            ->toContain('namespace App\\Livewire\\Admin')
            ->toContain('extends DataTable');

        File::delete($outputPath);
        if (File::isDirectory(app_path('Livewire/Admin')) && empty(File::files(app_path('Livewire/Admin')))) {
            File::deleteDirectory(app_path('Livewire/Admin'));
        }
    });

    it('creates class in subdirectory with backslash notation', function (): void {
        $outputPath = app_path('Livewire/Admin/BackslashTable.php');

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'Admin\\BackslashTable',
            'model' => Tests\Fixtures\Models\Post::class,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath))->toBeTrue();

        $contents = File::get($outputPath);

        expect($contents)->toContain('namespace App\\Livewire\\Admin');

        File::delete($outputPath);
        if (File::isDirectory(app_path('Livewire/Admin')) && empty(File::files(app_path('Livewire/Admin')))) {
            File::deleteDirectory(app_path('Livewire/Admin'));
        }
    });

    it('creates class in subdirectory with slash notation', function (): void {
        $outputPath = app_path('Livewire/Admin/SlashTable.php');

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'Admin/SlashTable',
            'model' => Tests\Fixtures\Models\Post::class,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath))->toBeTrue();

        $contents = File::get($outputPath);

        expect($contents)->toContain('namespace App\\Livewire\\Admin');

        File::delete($outputPath);
        if (File::isDirectory(app_path('Livewire/Admin')) && empty(File::files(app_path('Livewire/Admin')))) {
            File::deleteDirectory(app_path('Livewire/Admin'));
        }
    });

    it('resolves model by class basename via ModelFinder', function (): void {
        $outputPath = app_path('Livewire/FinderTestDataTable.php');

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'FinderTestDataTable',
            'model' => 'Post',
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath))->toBeTrue();

        $contents = File::get($outputPath);

        expect($contents)->toContain('extends DataTable');

        File::delete($outputPath);
    });

    it('uses provided model name when not resolvable', function (): void {
        $outputPath = app_path('Livewire/UnknownModelDataTable.php');

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'UnknownModelDataTable',
            'model' => 'NonExistentModel',
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath))->toBeTrue();

        $contents = File::get($outputPath);

        expect($contents)->toContain('NonExistentModel');

        File::delete($outputPath);
    });

    it('creates necessary directories automatically', function (): void {
        $dirPath = app_path('Livewire/Deep/Nested/Path');
        $outputPath = $dirPath . '/AutoDirTable.php';

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }
        if (File::isDirectory($dirPath)) {
            File::deleteDirectory($dirPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'Deep.Nested.Path.AutoDirTable',
            'model' => Tests\Fixtures\Models\Post::class,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::isDirectory($dirPath))->toBeTrue();
        expect(File::exists($outputPath))->toBeTrue();

        File::delete($outputPath);
        File::deleteDirectory(app_path('Livewire/Deep'));
    });

    it('generated class contents has correct namespace and model import', function (): void {
        $outputPath = app_path('Livewire/ContentCheckTable.php');

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'ContentCheckTable',
            'model' => Tests\Fixtures\Models\Post::class,
            '--force' => true,
        ])->assertSuccessful();

        $contents = File::get($outputPath);

        expect($contents)
            ->toContain('namespace App\\Livewire')
            ->toContain('use Tests\\Fixtures\\Models\\Post')
            ->toContain('class ContentCheckTable extends DataTable')
            ->toContain('protected string $model = Post::class')
            ->toContain('public function mount()');

        File::delete($outputPath);
    });
});

describe('MakeDataTableCommand custom stub', function (): void {
    it('uses custom stub from base_path stubs directory when available', function (): void {
        $stubDir = base_path('stubs');
        $stubPath = $stubDir . DIRECTORY_SEPARATOR . 'livewire.data-table.stub';
        $outputPath = app_path('Livewire/CustomStubTable.php');

        if (! File::isDirectory($stubDir)) {
            File::makeDirectory($stubDir, 0777, true, true);
        }

        File::put($stubPath, '<?php

namespace [namespace];

use TeamNiftyGmbH\DataTable\DataTable;
use [model_import];

// CUSTOM STUB MARKER
class [class] extends DataTable
{
    protected string $model = [model]::class;
}
');

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        $this->artisan('make:data-table', [
            'name' => 'CustomStubTable',
            'model' => Tests\Fixtures\Models\Post::class,
            '--force' => true,
        ])->assertSuccessful();

        $contents = File::get($outputPath);

        expect($contents)->toContain('CUSTOM STUB MARKER');

        File::delete($outputPath);
        File::delete($stubPath);
        if (File::isDirectory($stubDir) && empty(File::files($stubDir))) {
            File::deleteDirectory($stubDir);
        }
    });
});
