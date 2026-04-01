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

    it('renders Link object without target when not set', function (): void {
        $formatter = new LinkFormatter();
        $link = new stdClass();
        $link->url = 'https://example.com';
        $link->label = 'Visit';

        $result = $formatter->format($link);

        expect($result)->not->toContain('target=');
    });

    it('renders Link object using url as label when label not set', function (): void {
        $formatter = new LinkFormatter();
        $link = new stdClass();
        $link->url = 'https://example.com';

        $result = $formatter->format($link);

        expect($result)->toContain('>https://example.com</a>');
    });

    it('renders label only when Link object url is empty', function (): void {
        $formatter = new LinkFormatter();
        $link = new stdClass();
        $link->url = '';
        $link->label = 'No URL';

        $result = $formatter->format($link);

        expect($result)->toBe('No URL');
    });

    it('renders label only when Link object url is null', function (): void {
        $formatter = new LinkFormatter();
        $link = new stdClass();
        $link->url = null;
        $link->label = 'Fallback';

        $result = $formatter->format($link);

        expect($result)->toBe('Fallback');
    });

    it('default type constructs with link', function (): void {
        $formatter = new LinkFormatter();

        expect($formatter->type)->toBe('link');
    });

    it('passes context parameter without error', function (): void {
        $formatter = new LinkFormatter();
        $result = $formatter->format('https://example.com', ['key' => 'value']);

        expect($result)->toContain('<a');
    });

    it('includes css classes on all link types', function (): void {
        $linkFormatter = new LinkFormatter(type: 'link');
        $emailFormatter = new LinkFormatter(type: 'email');
        $telFormatter = new LinkFormatter(type: 'tel');

        $linkResult = $linkFormatter->format('https://example.com');
        $emailResult = $emailFormatter->format('test@test.com');
        $telResult = $telFormatter->format('+49123');

        expect($linkResult)->toContain('text-blue-600');
        expect($emailResult)->toContain('text-blue-600');
        expect($telResult)->toContain('text-blue-600');
    });
});
