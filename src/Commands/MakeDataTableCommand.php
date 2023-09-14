<?php

namespace TeamNiftyGmbH\DataTable\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportConsoleCommands\Commands\ComponentParser;
use Livewire\Features\SupportConsoleCommands\Commands\MakeLivewireCommand;
use Spatie\ModelInfo\ModelFinder;

class MakeDataTableCommand extends GeneratorCommand
{
    protected ComponentParser|\Livewire\Commands\ComponentParser $parser;

    protected string $model;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:data-table
        {name : The name of the component}
        {model : The name of the model you want to use in this table}
        {--force}
        {--stub}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Livewire DataTable component';

    /**
     * Execute the console command.
     */
    public function handle(): bool
    {
        $version = InstalledVersions::getVersion('livewire/livewire');

        if (version_compare($version, '3.0.0', '<')) {
            $this->parser = new \Livewire\Commands\ComponentParser(
                config('tall-datatables.data_table_namespace'),
                config('tall-datatables.view_path'),
                $this->argument('name'),
            );
            $livewireMakeCommand = new \Livewire\Commands\MakeCommand();
        } else {
            $this->parser = new ComponentParser(
                config('tall-datatables.data_table_namespace'),
                config('tall-datatables.view_path'),
                $this->argument('name'),
            );
            $livewireMakeCommand = new MakeLivewireCommand();
        }

        if ($livewireMakeCommand->isReservedClassName($name = $this->parser->className())) {
            $this->line('<fg=red;options=bold>Class is reserved:</>' . $name);

            return false;
        }

        if (class_exists($this->argument('model'))) {
            $this->model = $this->argument('model');
        } else {
            $model = ModelFinder::all()
                ->filter(
                    function ($modelInfo) {
                        return class_basename($modelInfo) === $this->argument('model')
                            || $modelInfo === $this->argument('model');
                    }
                )
                ->first();
            $this->model = $model ?: $this->argument('model');
        }
        $force = $this->option('force');

        if ($classPath = $this->createClass($force)) {
            $this->info('Livewire Datatable Created: ' . $classPath);
        }

        return true;
    }

    private function createClass(bool $force = false): string|bool
    {
        $classPath = $this->parser->classPath();

        if (! $force && File::exists($classPath)) {
            $this->line('<fg=red;options=bold>Class already exists:</> ' . $this->parser->relativeClassPath());

            return false;
        }

        $this->ensureDirectoryExists($classPath);

        File::put($classPath, $this->classContents());

        return $classPath;
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (! File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0777, true, true);
        }
    }

    private function classContents(): string
    {
        return str_replace(
            ['[namespace]', '[class]', '[model]', '[model_import]', '[columns]'],
            [$this->parser->classNamespace(), $this->parser->className(), class_basename($this->model), $this->model],
            $this->getStub()
        );
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        if (File::exists($stubPath = base_path('stubs' . DIRECTORY_SEPARATOR . 'livewire.data-table.stub'))) {
            return file_get_contents($stubPath);
        }

        return file_get_contents(__DIR__ . '/../../stubs/livewire.data-table.stub');
    }
}
