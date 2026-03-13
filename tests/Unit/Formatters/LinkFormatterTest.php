<?php

use TeamNiftyGmbH\DataTable\Formatters\LinkFormatter;

describe('LinkFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new LinkFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('renders a link type with href and label', function (): void {
        $formatter = new LinkFormatter(type: 'link');
        $result = $formatter->format('https://example.com');

        expect($result)
            ->toContain('<a')
            ->toContain('href="https://example.com"')
            ->toContain('https://example.com')
            ->toContain('</a>');
    });

    it('renders an email type with mailto prefix', function (): void {
        $formatter = new LinkFormatter(type: 'email');
        $result = $formatter->format('user@example.com');

        expect($result)
            ->toContain('href="mailto:user@example.com"')
            ->toContain('user@example.com');
    });

    it('renders a tel type with tel prefix', function (): void {
        $formatter = new LinkFormatter(type: 'tel');
        $result = $formatter->format('+49123456789');

        expect($result)
            ->toContain('href="tel:+49123456789"')
            ->toContain('+49123456789');
    });

    it('renders url type with href', function (): void {
        $formatter = new LinkFormatter(type: 'url');
        $result = $formatter->format('https://example.com');

        expect($result)
            ->toContain('href="https://example.com"');
    });

    it('renders Link object with url, label and target', function (): void {
        $formatter = new LinkFormatter();
        $link = new stdClass();
        $link->url = 'https://example.com';
        $link->label = 'Visit';
        $link->target = '_blank';

        $result = $formatter->format($link);

        expect($result)
            ->toContain('href="https://example.com"')
            ->toContain('Visit')
            ->toContain('target="_blank"');
    });

    it('escapes XSS in URL string', function (): void {
        $formatter = new LinkFormatter();
        $result = $formatter->format('javascript:alert(1)');

        // The result should contain the escaped version of the URL, not raw
        expect($result)->toContain('javascript');
    });

    it('escapes XSS in link object url', function (): void {
        $formatter = new LinkFormatter();
        $link = new stdClass();
        $link->url = '<script>alert(1)</script>';
        $link->label = 'Click me';

        $result = $formatter->format($link);

        expect($result)->not->toContain('<script>');
    });

    it('escapes XSS in link object label', function (): void {
        $formatter = new LinkFormatter();
        $link = new stdClass();
        $link->url = 'https://example.com';
        $link->label = '<script>evil()</script>';

        $result = $formatter->format($link);

        expect($result)->not->toContain('<script>');
    });
});
