<?php

use Illuminate\View\ComponentAttributeBag;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableRowAttributes;

describe('DataTableRowAttributes', function (): void {
    it('extends ComponentAttributeBag', function (): void {
        $attributes = DataTableRowAttributes::make();

        expect($attributes)->toBeInstanceOf(ComponentAttributeBag::class);
    });

    it('can be created with make method', function (): void {
        $attributes = DataTableRowAttributes::make();

        expect($attributes)->toBeInstanceOf(DataTableRowAttributes::class);
    });

    it('can bind attributes with x-bind prefix', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->bind('class', "record.is_active ? 'bg-green-100' : 'bg-red-100'");

        expect($attributes->getAttributes())
            ->toHaveKey('x-bind:class', "record.is_active ? 'bg-green-100' : 'bg-red-100'");
    });

    it('can add event listeners with x-on prefix', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->on('click', 'handleRowClick(record)');

        expect($attributes->getAttributes())
            ->toHaveKey('x-on:click', 'handleRowClick(record)');
    });

    it('can bind multiple attributes', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->bind('class', 'rowClass')
            ->bind('style', 'rowStyle')
            ->bind('data-id', 'record.id');

        $attrs = $attributes->getAttributes();

        expect($attrs)
            ->toHaveKey('x-bind:class', 'rowClass')
            ->toHaveKey('x-bind:style', 'rowStyle')
            ->toHaveKey('x-bind:data-id', 'record.id');
    });

    it('can add multiple event listeners', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->on('click', 'handleClick()')
            ->on('dblclick', 'handleDoubleClick()')
            ->on('mouseenter', 'handleHover()');

        $attrs = $attributes->getAttributes();

        expect($attrs)
            ->toHaveKey('x-on:click', 'handleClick()')
            ->toHaveKey('x-on:dblclick', 'handleDoubleClick()')
            ->toHaveKey('x-on:mouseenter', 'handleHover()');
    });

    it('supports method chaining', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->bind('class', 'dynamicClass')
            ->on('click', 'handleClick()')
            ->bind('title', 'tooltipText')
            ->on('contextmenu', 'openContextMenu($event)');

        $attrs = $attributes->getAttributes();

        expect($attrs)->toHaveCount(4);
        expect($attrs)->toHaveKey('x-bind:class');
        expect($attrs)->toHaveKey('x-on:click');
        expect($attrs)->toHaveKey('x-bind:title');
        expect($attrs)->toHaveKey('x-on:contextmenu');
    });
});

describe('DataTableRowAttributes Common Use Cases', function (): void {
    it('can create conditional row classes', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->bind('class', "{'bg-green-50': record.status === 'active', 'bg-red-50': record.status === 'inactive'}");

        expect($attributes->getAttributes())
            ->toHaveKey('x-bind:class');
    });

    it('can create row click navigation', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->on('click', "window.location.href = '/items/' + record.id");

        expect($attributes->getAttributes())
            ->toHaveKey('x-on:click');
    });

    it('can bind data attributes for javascript', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->bind('data-id', 'record.id')
            ->bind('data-type', 'record.type')
            ->bind('data-status', 'record.status');

        $attrs = $attributes->getAttributes();

        expect($attrs)
            ->toHaveKey('x-bind:data-id')
            ->toHaveKey('x-bind:data-type')
            ->toHaveKey('x-bind:data-status');
    });

    it('can handle keyboard events', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->on('keydown.enter', 'selectRow(record)')
            ->on('keydown.space', 'toggleSelection(record)');

        $attrs = $attributes->getAttributes();

        expect($attrs)
            ->toHaveKey('x-on:keydown.enter')
            ->toHaveKey('x-on:keydown.space');
    });

    it('can handle mouse events with modifiers', function (): void {
        $attributes = DataTableRowAttributes::make()
            ->on('click.prevent', 'handleClick()')
            ->on('click.stop', 'handleClickStop()');

        $attrs = $attributes->getAttributes();

        expect($attrs)
            ->toHaveKey('x-on:click.prevent')
            ->toHaveKey('x-on:click.stop');
    });
});
