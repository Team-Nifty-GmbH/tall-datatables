<?php

use TeamNiftyGmbH\DataTable\Exports\CsvExport;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'CSV User', 'email' => 'csv@test.com']);
    $this->actingAs($this->user);
});

describe('CsvExport', function (): void {
    it('returns a streamed response', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'CSV Post']);

        $export = new CsvExport(Post::query(), ['title', 'content']);
        $response = $export->download('test.csv');

        expect($response)->toBeInstanceOf(Symfony\Component\HttpFoundation\StreamedResponse::class);
    });

    it('sets correct content type header', function (): void {
        $export = new CsvExport(Post::query(), ['title']);
        $response = $export->download('test.csv');

        expect($response->headers->get('content-type'))->toContain('text/csv');
    });

    it('sets content disposition header with filename', function (): void {
        $export = new CsvExport(Post::query(), ['title']);
        $response = $export->download('export.csv');

        expect($response->headers->get('content-disposition'))->toContain('export.csv');
    });

    it('outputs UTF-8 BOM and semicolon-separated headings', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test']);

        $export = new CsvExport(Post::query(), ['title', 'content']);
        $response = $export->download('test.csv');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $bom = "\xEF\xBB\xBF";
        expect($output)->toStartWith($bom);

        $lines = explode("\n", substr($output, strlen($bom)));
        expect($lines[0])->toContain(__('Title'))
            ->toContain(';')
            ->toContain(__('Content'));
    });

    it('outputs data rows with semicolon separator', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Row Test', 'content' => 'Body text']);

        $export = new CsvExport(Post::query(), ['title', 'content']);
        $response = $export->download('test.csv');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toContain('Row Test')
            ->toContain('Body text');
    });

    it('handles relation columns', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Rel']);

        $export = new CsvExport(Post::query()->with('user'), ['title', 'user.name']);
        $response = $export->download('test.csv');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toContain('CSV User');
    });
});
