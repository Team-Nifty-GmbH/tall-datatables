<?php

use Livewire\Attributes\Locked;
use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Livewire\Options;

describe('Livewire Options Component', function (): void {
    describe('class structure', function (): void {
        it('extends Livewire Component', function (): void {
            $reflection = new ReflectionClass(Options::class);

            expect($reflection->isSubclassOf(\Livewire\Component::class))->toBeTrue();
        });

        it('has render method that returns a View', function (): void {
            $reflection = new ReflectionClass(Options::class);

            expect($reflection->hasMethod('render'))->toBeTrue();

            $returnType = $reflection->getMethod('render')->getReturnType();
            expect($returnType->getName())->toBe(\Illuminate\View\View::class);
        });
    });

    describe('locked properties', function (): void {
        it('aggregatable property has Locked attribute', function (): void {
            $reflection = new ReflectionProperty(Options::class, 'aggregatable');
            $attributes = $reflection->getAttributes(Locked::class);

            expect($attributes)->toHaveCount(1);
        });

        it('allowSoftDeletes property has Locked attribute', function (): void {
            $reflection = new ReflectionProperty(Options::class, 'allowSoftDeletes');
            $attributes = $reflection->getAttributes(Locked::class);

            expect($attributes)->toHaveCount(1);
        });

        it('isExportable property has Locked attribute', function (): void {
            $reflection = new ReflectionProperty(Options::class, 'isExportable');
            $attributes = $reflection->getAttributes(Locked::class);

            expect($attributes)->toHaveCount(1);
        });

        it('isFilterable property has Locked attribute', function (): void {
            $reflection = new ReflectionProperty(Options::class, 'isFilterable');
            $attributes = $reflection->getAttributes(Locked::class);

            expect($attributes)->toHaveCount(1);
        });
    });

    describe('property defaults', function (): void {
        it('aggregatable defaults to null', function (): void {
            $instance = new Options();

            expect($instance->aggregatable)->toBeNull();
        });

        it('allowSoftDeletes defaults to null', function (): void {
            $instance = new Options();

            expect($instance->allowSoftDeletes)->toBeNull();
        });

        it('isExportable defaults to null', function (): void {
            $instance = new Options();

            expect($instance->isExportable)->toBeNull();
        });

        it('isFilterable defaults to null', function (): void {
            $instance = new Options();

            expect($instance->isFilterable)->toBeNull();
        });
    });

    describe('property types', function (): void {
        it('aggregatable is nullable array', function (): void {
            $reflection = new ReflectionProperty(Options::class, 'aggregatable');

            expect($reflection->getType()->getName())->toBe('array');
            expect($reflection->getType()->allowsNull())->toBeTrue();
        });

        it('allowSoftDeletes is nullable bool', function (): void {
            $reflection = new ReflectionProperty(Options::class, 'allowSoftDeletes');

            expect($reflection->getType()->getName())->toBe('bool');
            expect($reflection->getType()->allowsNull())->toBeTrue();
        });

        it('isExportable is nullable bool', function (): void {
            $reflection = new ReflectionProperty(Options::class, 'isExportable');

            expect($reflection->getType()->getName())->toBe('bool');
            expect($reflection->getType()->allowsNull())->toBeTrue();
        });

        it('isFilterable is nullable bool', function (): void {
            $reflection = new ReflectionProperty(Options::class, 'isFilterable');

            expect($reflection->getType()->getName())->toBe('bool');
            expect($reflection->getType()->allowsNull())->toBeTrue();
        });
    });

    describe('render method', function (): void {
        it('returns a View instance', function (): void {
            $instance = new Options();
            $instance->setId('test-options');

            // render() should return a View instance
            $result = app()->call([$instance, 'render']);

            expect($result)->toBeInstanceOf(\Illuminate\View\View::class);
        });

        it('renders the options view template', function (): void {
            $instance = new Options();
            $instance->setId('test-options-view');

            $result = app()->call([$instance, 'render']);

            expect($result->getName())->toBe('tall-datatables::components.options');
        });
    });

    describe('render view name', function (): void {
        it('uses the correct view template', function (): void {
            $instance = new Options();
            $instance->setId('options-view-test');

            $view = app()->call([$instance, 'render']);

            expect($view->getName())->toBe('tall-datatables::components.options');
        });
    });
});
