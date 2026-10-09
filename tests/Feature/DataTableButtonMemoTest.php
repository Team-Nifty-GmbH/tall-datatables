<?php

use Illuminate\Support\Facades\View;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableButton;

function countingButtonRenders(callable $fn): int
{
    $renders = 0;
    View::composer('*', function () use (&$renders): void {
        $renders++;
    });

    $fn();

    return $renders;
}

it('renders a row action once, however often it is echoed', function (): void {
    $button = DataTableButton::make(color: 'indigo', text: 'Edit', icon: 'pencil');

    // warm up, so the compile of the button template is not counted
    $button->toHtml();

    $first = null;
    $renders = countingButtonRenders(function () use ($button, &$first): void {
        $first = $button->toHtml();
        $button->toHtml();
        $button->toHtml();
    });

    expect($renders)->toBe(0)
        ->and($first)->toContain('<button');
});

it('renders again once the button changed', function (): void {
    $button = DataTableButton::make(color: 'indigo', text: 'Edit');

    $before = $button->toHtml();
    $after = $button->text('Delete')->toHtml();

    expect($after)->not->toBe($before)
        ->and($after)->toContain('Delete');
});

it('renders again once an attribute changed', function (): void {
    $button = DataTableButton::make(color: 'indigo', text: 'Edit');

    $before = $button->toHtml();
    $after = $button->xOnClick('doSomething()')->toHtml();

    expect($after)->not->toBe($before)
        ->and($after)->toContain('doSomething()');
});
