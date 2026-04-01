<?php

use TeamNiftyGmbH\DataTable\Helpers\DataTableTagCompiler;

describe('DataTableTagCompiler', function (): void {
    describe('compile', function (): void {
        it('compiles datatable scripts self-closing tag', function (): void {
            $compiler = app(DataTableTagCompiler::class);
            $result = $compiler->compile('<datatable:scripts />');

            expect($result)
                ->toContain('<script')
                ->toContain('src=')
                ->toContain('defer');
        });

        it('compiles datatable styles self-closing tag', function (): void {
            $compiler = app(DataTableTagCompiler::class);
            $result = $compiler->compile('<datatable:styles />');

            expect($result)
                ->toContain('<link')
                ->toContain('href=')
                ->toContain('stylesheet');
        });

        it('compiles scripts tag without space before slash', function (): void {
            $compiler = app(DataTableTagCompiler::class);
            $result = $compiler->compile('<datatable:scripts/>');

            expect($result)
                ->toContain('<script')
                ->toContain('src=');
        });

        it('compiles styles tag without space before slash', function (): void {
            $compiler = app(DataTableTagCompiler::class);
            $result = $compiler->compile('<datatable:styles/>');

            expect($result)
                ->toContain('<link')
                ->toContain('href=');
        });

        it('compiles scripts tag without trailing slash', function (): void {
            $compiler = app(DataTableTagCompiler::class);
            $result = $compiler->compile('<datatable:scripts>');

            expect($result)
                ->toContain('<script')
                ->toContain('src=');
        });

        it('compiles styles tag without trailing slash', function (): void {
            $compiler = app(DataTableTagCompiler::class);
            $result = $compiler->compile('<datatable:styles>');

            expect($result)
                ->toContain('<link')
                ->toContain('href=');
        });

        it('returns value unchanged when no datatable tags present', function (): void {
            $compiler = app(DataTableTagCompiler::class);
            $input = '<div>Some HTML content</div>';
            $result = $compiler->compile($input);

            expect($result)->toBe($input);
        });

        it('compiles multiple datatable tags in one string', function (): void {
            $compiler = app(DataTableTagCompiler::class);
            $result = $compiler->compile('<datatable:styles /><datatable:scripts />');

            expect($result)
                ->toContain('<link')
                ->toContain('<script');
        });

        it('preserves surrounding HTML content', function (): void {
            $compiler = app(DataTableTagCompiler::class);
            $result = $compiler->compile('<head><datatable:styles /></head>');

            expect($result)
                ->toContain('<head>')
                ->toContain('</head>')
                ->toContain('<link');
        });
    });
});
