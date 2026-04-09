<?php

namespace TeamNiftyGmbH\DataTable\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeDataTableCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Livewire DataTable component';

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
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $namespace = $this->resolveNamespace();
        $className = class_basename($name);

        $reservedNames = ['parent', 'component', 'interface', 'abstract', 'class', 'static', 'self'];

        if (in_array(strtolower($className), $reservedNames)) {
            $this->line('<fg=red;options=bold>Class is reserved:</>' . $className);

            return self::FAILURE;
        }

        if (class_exists($this->argument('model'))) {
            $this->model = $this->argument('model');
        } else {
            // Try to resolve from morph map
            $model = collect(Relation::morphMap())
                ->filter(
                    fn (string $modelClass) => class_basename($modelClass) === $this->argument('model')
                        || $modelClass === $this->argument('model')
                )
                ->first();
            $this->model = $model ?: $this->argument('model');
        }

        $force = $this->option('force');
        $classPath = $this->resolveClassPath($name);

        if ($created = $this->createClass($classPath, $namespace, $className, $force)) {
            $this->info('Livewire Datatable Created: ' . $created);
        }

        return self::SUCCESS;
    }

    protected function classContents(string $namespace, string $className): string
    {
        return str_replace(
            ['[namespace]', '[class]', '[model]', '[model_import]'],
            [$namespace, $className, class_basename($this->model), $this->model],
            $this->getStub()
        );
    }

    protected function createClass(string $classPath, string $namespace, string $className, bool $force = false): string|bool
    {
        if (! $force && File::exists($classPath)) {
            $this->line('<fg=red;options=bold>Class already exists:</> ' . str_replace(base_path() . '/', '', $classPath));

            return false;
        }

        $this->ensureDirectoryExists($classPath);

        File::put($classPath, $this->classContents($namespace, $className));

        return $classPath;
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0777, true, true);
        }
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

    protected function resolveClassPath(string $name): string
    {
        $namespace = config('tall-datatables.data_table_namespace', 'App\\Livewire');
        $basePath = Str::of($namespace)
            ->replaceFirst('App\\', '')
            ->replace('\\', '/')
            ->prepend(app_path() . '/');

        $subPath = Str::of($name)
            ->replace('.', '/')
            ->replace('\\', '/');

        return $basePath . '/' . $subPath . '.php';
    }

    protected function resolveNamespace(): string
    {
        $baseNamespace = config('tall-datatables.data_table_namespace', 'App\\Livewire');
        $name = $this->argument('name');

        if (str_contains($name, '.') || str_contains($name, '\\') || str_contains($name, '/')) {
            $parts = preg_split('/[.\\\\\\/]/', $name);
            array_pop($parts);

            if (! empty($parts)) {
                return $baseNamespace . '\\' . implode('\\', $parts);
            }
        }

        return $baseNamespace;
    }
}
