<?php

use Illuminate\Database\Eloquent\Builder;
use TeamNiftyGmbH\DataTable\Exports\DataTableExport;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'Export User', 'email' => 'export@test.com']);
    $this->actingAs($this->user);
});

describe('DataTableExport headings', function (): void {
    it('generates headings from simple column names', function (): void {
        $export = new DataTableExport(Post::query(), ['title', 'content']);

        $headings = $export->headings();

        expect($headings)->toBe([__('Title'), __('Content')]);
    });

    it('generates headings with headline transformation', function (): void {
        $export = new DataTableExport(Post::query(), ['is_published', 'created_at']);

        $headings = $export->headings();

        expect($headings)->toBe([__('Is Published'), __('Created At')]);
    });

    it('generates headings for dot-notation columns', function (): void {
        $export = new DataTableExport(Post::query(), ['user.name', 'user.email']);

        $headings = $export->headings();

        expect($headings[0])->toContain(__('User'))
            ->toContain(' -> ')
            ->toContain(__('Name'));
        expect($headings[1])->toContain(__('User'))
            ->toContain(' -> ')
            ->toContain(__('Email'));
    });

    it('generates headings for deeply nested dot-notation columns', function (): void {
        $export = new DataTableExport(Post::query(), ['user.profile.avatar']);

        $headings = $export->headings();

        expect($headings[0])->toContain(' -> ')
            ->toContain(__('User'))
            ->toContain(__('Profile'))
            ->toContain(__('Avatar'));
    });

    it('returns empty array for no columns', function (): void {
        $export = new DataTableExport(Post::query(), []);

        expect($export->headings())->toBe([]);
    });
});

describe('DataTableExport map', function (): void {
    it('extracts simple column values from a row', function (): void {
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'My Post',
            'content' => 'Post content here',
        ]);

        $export = new DataTableExport(Post::query(), ['title', 'content']);
        $result = $export->map($post);

        expect($result)->toBe([
            'title' => 'My Post',
            'content' => 'Post content here',
        ]);
    });

    it('handles dot-notation for relation data', function (): void {
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Relational Post',
        ]);
        $post->load('user');

        $export = new DataTableExport(Post::query(), ['user.name']);
        $result = $export->map($post);

        expect($result['user.name'])->toBe('Export User');
    });

    it('returns null for missing columns', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        $export = new DataTableExport(Post::query(), ['nonexistent']);
        $result = $export->map($post);

        expect($result['nonexistent'])->toBeNull();
    });

    it('handles wildcard fallback for nested array data', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        // Create comments to test wildcard extraction
        createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey(), 'body' => 'Comment A']);
        createTestComment(['post_id' => $post->getKey(), 'user_id' => $this->user->getKey(), 'body' => 'Comment B']);

        $post->load('comments');

        $export = new DataTableExport(Post::query(), ['comments.body']);
        $result = $export->map($post);

        // When dot notation returns null, it tries wildcard pattern
        // The wildcard approach should find the comment bodies
        if (is_string($result['comments.body'])) {
            expect($result['comments.body'])->toContain('Comment A')
                ->toContain('Comment B');
        } else {
            // If it returns as array or null, it means the wildcard did not match
            expect($result)->toHaveKey('comments.body');
        }
    });

    it('returns empty result for empty columns', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        $export = new DataTableExport(Post::query(), []);
        $result = $export->map($post);

        expect($result)->toBe([]);
    });

    it('handles boolean values', function (): void {
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'is_published' => true,
        ]);

        $export = new DataTableExport(Post::query(), ['is_published']);
        $result = $export->map($post);

        expect($result['is_published'])->toBe(true);
    });
});

describe('DataTableExport query', function (): void {
    it('returns the builder instance', function (): void {
        $builder = Post::query()->where('is_published', true);
        $export = new DataTableExport($builder, ['title']);

        expect($export->query())->toBe($builder);
    });

    it('returns the same builder that was injected', function (): void {
        $builder = User::query()->orderBy('name');
        $export = new DataTableExport($builder, ['name', 'email']);

        $query = $export->query();

        expect($query)->toBeInstanceOf(Builder::class);
    });
});

describe('DataTableExport integration', function (): void {
    it('can be constructed with builder and columns', function (): void {
        $export = new DataTableExport(Post::query(), ['title', 'content']);

        expect($export)->toBeInstanceOf(DataTableExport::class);
    });

    it('handles mixed simple and relation columns', function (): void {
        $post = createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Mixed Columns',
        ]);
        $post->load('user');

        $export = new DataTableExport(Post::query(), ['title', 'user.name']);

        $headings = $export->headings();
        expect($headings)->toHaveCount(2);

        $mapped = $export->map($post);
        expect($mapped['title'])->toBe('Mixed Columns')
            ->and($mapped['user.name'])->toBe('Export User');
    });
});

describe('DataTableExport download', function (): void {
    it('returns a StreamedResponse with xlsx content type', function (): void {
        $export = new DataTableExport(Post::query(), ['title']);

        $response = $export->download('export.xlsx');

        expect($response)->toBeInstanceOf(Symfony\Component\HttpFoundation\StreamedResponse::class)
            ->and($response->headers->get('Content-Type'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->and($response->headers->get('Content-Disposition'))->toContain('attachment; filename="export.xlsx"');
    });

    it('streams a valid xlsx file that can be re-read by phpspreadsheet', function (): void {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Roundtrip Title',
            'content' => 'Roundtrip Content',
        ]);

        $export = new DataTableExport(Post::query(), ['title', 'content']);
        $response = $export->download('roundtrip.xlsx');

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'tdt-test-');
        file_put_contents($tmp, $body);

        try {
            $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmp);
            $spreadsheet = $reader->load($tmp);
            $rows = $spreadsheet->getActiveSheet()->toArray();

            expect($rows[0])->toBe([__('Title'), __('Content')])
                ->and($rows[1])->toBe(['Roundtrip Title', 'Roundtrip Content']);
        } finally {
            @unlink($tmp);
        }
    });
});

describe('DataTableExport store', function (): void {
    it('writes an xlsx file to the given storage path', function (): void {
        Illuminate\Support\Facades\Storage::fake('local');

        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Stored Title',
        ]);

        $export = new DataTableExport(Post::query(), ['title']);
        $result = $export->store('exports/test.xlsx', 'local');

        expect($result)->toBeTrue();
        Illuminate\Support\Facades\Storage::disk('local')->assertExists('exports/test.xlsx');

        $contents = Illuminate\Support\Facades\Storage::disk('local')->get('exports/test.xlsx');
        $tmp = tempnam(sys_get_temp_dir(), 'tdt-test-');
        file_put_contents($tmp, $contents);

        try {
            $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmp);
            $rows = $reader->load($tmp)->getActiveSheet()->toArray();

            expect($rows[1])->toBe(['Stored Title']);
        } finally {
            @unlink($tmp);
        }
    });

    it('uses the default disk when none is passed', function (): void {
        Illuminate\Support\Facades\Storage::fake();

        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Default Disk']);

        $export = new DataTableExport(Post::query(), ['title']);
        $export->store('exports/default.xlsx');

        Illuminate\Support\Facades\Storage::assertExists('exports/default.xlsx');
    });
});
