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
