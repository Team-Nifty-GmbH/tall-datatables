<?php

namespace TeamNiftyGmbH\DataTable\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Livewire\Commands\ComponentParser;
use Livewire\Commands\MakeCommand as LivewireMakeCommand;
use Spatie\ModelInfo\ModelFinder;

class MakeDataTableCommand extends GeneratorCommand
{
    protected ComponentParser $parser;

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
     * @return bool
     */
    public function handle(): bool
    {
        $this->parser = new ComponentParser(
            config('tall-datatables.data_table_namespace'),
            config('tall-datatables.view_path'),
            $this->argument('name'),
        );

        $livewireMakeCommand = new LivewireMakeCommand();

        if ($livewireMakeCommand->isReservedClassName($name = $this->parser->className())) {
            $this->line('<fg=red;options=bold>Class is reserved:</>' . $name);

            return false;
        }

        $this->model = ModelFinder::all()
            ->filter(
                function ($modelInfo) {
                    return class_basename($modelInfo) === $this->argument('model')
                        || $modelInfo === $this->argument('model');
                }
            )
            ->first();
        $force = $this->option('force');

        if ($classPath = $this->createClass($force)) {
            $this->info('Livewire Datatable Created: ' . $classPath);
        }

        return true;
    }

    /**
     * @param bool $force
     * @return string|bool
     */
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

    /**
     * @param string $path
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (! File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * @return string
     */
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
     *
     * @return string
     */
    protected function getStub(): string
    {
        if (File::exists($stubPath = base_path('stubs' . DIRECTORY_SEPARATOR . 'livewire.data-table.stub'))) {
            return file_get_contents($stubPath);
        }

        return file_get_contents(__DIR__ . '/../../stubs/livewire.data-table.stub');
    }
}
