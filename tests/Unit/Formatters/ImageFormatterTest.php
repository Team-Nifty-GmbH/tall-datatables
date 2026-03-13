<?php

use TeamNiftyGmbH\DataTable\Formatters\ImageFormatter;

describe('ImageFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new ImageFormatter;

        expect($formatter->format(null))->toBe('');
    });

    it('renders img tag for URL string', function (): void {
        $formatter = new ImageFormatter;
        $result = $formatter->format('https://example.com/image.jpg');

        expect($result)
            ->toContain('<img')
            ->toContain('src="https://example.com/image.jpg"')
            ->toContain('/>');
    });

    it('renders img tag with default classes for URL string', function (): void {
        $formatter = new ImageFormatter;
        $result = $formatter->format('https://example.com/image.jpg');

        expect($result)->toContain('h-8 w-8 object-cover rounded');
    });

    it('renders Image object with src, alt, title, class', function (): void {
        $formatter = new ImageFormatter;
        $image = new stdClass;
        $image->src = 'https://example.com/photo.jpg';
        $image->alt = 'A photo';
        $image->title = 'My Photo';
        $image->class = 'h-12 w-12';

        $result = $formatter->format($image);

        expect($result)
            ->toContain('src="https://example.com/photo.jpg"')
            ->toContain('alt="A photo"')
            ->toContain('title="My Photo"')
            ->toContain('class="h-12 w-12"');
    });

    it('escapes XSS in src', function (): void {
        $formatter = new ImageFormatter;
        $result = $formatter->format('"><script>alert(1)</script>');

        expect($result)->not->toContain('<script>');
    });

    it('escapes XSS in Image object src', function (): void {
        $formatter = new ImageFormatter;
        $image = new stdClass;
        $image->src = '"><script>alert(1)</script>';

        $result = $formatter->format($image);

        expect($result)->not->toContain('<script>');
    });

    it('escapes XSS in alt text', function (): void {
        $formatter = new ImageFormatter;
        $image = new stdClass;
        $image->src = 'https://example.com/image.jpg';
        $image->alt = '<script>evil()</script>';

        $result = $formatter->format($image);

        expect($result)->not->toContain('<script>');
    });

    it('returns empty string for Image object with empty src', function (): void {
        $formatter = new ImageFormatter;
        $image = new stdClass;
        $image->src = '';

        expect($formatter->format($image))->toBe('');
    });
});
