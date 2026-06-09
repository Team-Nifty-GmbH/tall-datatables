<?php

use Symfony\Component\Finder\Finder;

it('has no bare wire:click directives without parentheses in blade views', function (): void {
    $finder = (new Finder())
        ->files()
        ->in(__DIR__ . '/../../resources/views')
        ->name('*.blade.php');

    $offenders = [];

    foreach ($finder as $file) {
        $content = $file->getContents();

        if (preg_match_all('/wire:click="([a-zA-Z_][a-zA-Z0-9_]*)"/', $content, $matches)) {
            foreach ($matches[0] as $match) {
                $offenders[] = $file->getRelativePathname() . ' :: ' . $match;
            }
        }
    }

    expect($offenders)->toBe([], implode("\n", $offenders));
});

it('has no bare x-on:click directives referencing $wire methods without prefix', function (): void {
    $finder = (new Finder())
        ->files()
        ->in(__DIR__ . '/../../resources/views')
        ->name('*.blade.php');

    $offenders = [];
    $wireMethods = [
        'resetLayout',
        'saveDefaultColumns',
        'deleteDefaultColumns',
        'saveFilter',
        'loadFilter',
        'clearFilters',
        'clearFiltersAndSort',
        'storeColLayout',
        'deleteSavedFilter',
        'updateSavedFilter',
    ];

    foreach ($finder as $file) {
        $content = $file->getContents();

        foreach ($wireMethods as $method) {
            if (preg_match('/x-on:click="' . preg_quote($method, '/') . '"/', $content)) {
                $offenders[] = $file->getRelativePathname() . ' :: x-on:click="' . $method . '"';
            }
        }
    }

    expect($offenders)->toBe([], implode("\n", $offenders));
});
