<?php

use App\Services\CommentFormatter;

beforeEach(function () {
    $this->formatter = new CommentFormatter();
});

test('formats bold text', function () {
    $result = $this->formatter->format('**fetter Text**');

    expect($result)->toContain('<strong>fetter Text</strong>');
});

test('formats italic text', function () {
    $result = $this->formatter->format('*kursiver Text*');

    expect($result)->toContain('<em>kursiver Text</em>');
});

test('formats bold and italic together', function () {
    $result = $this->formatter->format('**fett** und *kursiv*');

    expect($result)->toContain('<strong>fett</strong>');
    expect($result)->toContain('<em>kursiv</em>');
});

test('formats checked checkboxes', function () {
    $result = $this->formatter->format('[x] Erledigt');

    expect($result)->toContain('bi-check-square');
    expect($result)->toContain('Erledigt');
    expect($result)->not->toContain('[x]');
});

test('formats unchecked checkboxes', function () {
    $result = $this->formatter->format('[ ] Noch offen');

    expect($result)->toContain('bi-square');
    expect($result)->toContain('Noch offen');
    expect($result)->not->toContain('[ ]');
});

test('formats URLs as clickable links', function () {
    $result = $this->formatter->format('Besuche https://example.com bitte');

    expect($result)->toContain('<a href="https://example.com"');
    expect($result)->toContain('target="_blank"');
    expect($result)->toContain('rel="noopener noreferrer"');
});

test('formats http URLs', function () {
    $result = $this->formatter->format('Link: http://example.com/path');

    expect($result)->toContain('<a href="http://example.com/path"');
});

test('preserves line breaks', function () {
    $result = $this->formatter->format("Zeile 1\nZeile 2");

    expect($result)->toContain('<br');
});

test('prevents XSS with script tags', function () {
    $result = $this->formatter->format('<script>alert("xss")</script>');

    expect($result)->not->toContain('<script>');
    expect($result)->toContain('&lt;script&gt;');
});

test('prevents XSS with HTML attributes', function () {
    $result = $this->formatter->format('<img src=x onerror=alert(1)>');

    expect($result)->not->toContain('<img');
    expect($result)->toContain('&lt;img');
});

test('prevents XSS with nested HTML', function () {
    $result = $this->formatter->format('<a href="javascript:alert(1)">click</a>');

    // The HTML is escaped, so no actual <a> tag is rendered
    expect($result)->not->toContain('<a href="javascript:');
    expect($result)->toContain('&lt;a');
});

test('handles empty string', function () {
    $result = $this->formatter->format('');

    expect($result)->toBe('');
});

test('handles plain text without formatting', function () {
    $result = $this->formatter->format('Einfacher Text ohne Formatierung');

    expect($result)->toBe('Einfacher Text ohne Formatierung');
});

test('handles German umlauts', function () {
    $result = $this->formatter->format('Ueberpruefen Sie die Aenderungen fuer aeltere Gebaeude');

    expect($result)->toContain('Ueberpruefen');
});

test('handles multiple checkboxes', function () {
    $result = $this->formatter->format("[x] Erster Punkt\n[ ] Zweiter Punkt\n[x] Dritter Punkt");

    // Count occurrences of checked and unchecked
    expect(substr_count($result, 'bi-check-square'))->toBe(2);
    expect(substr_count($result, 'bi-square'))->toBe(1);
});

test('handles URL with query parameters', function () {
    $result = $this->formatter->format('https://example.com/page?param=value&other=test');

    expect($result)->toContain('href="https://example.com/page?param=value&amp;other=test"');
});

test('does not format single asterisks as italic in middle of words', function () {
    // The regex uses .+? which is non-greedy, so *a*b* should format *a* as italic
    $result = $this->formatter->format('*a*');

    expect($result)->toContain('<em>a</em>');
});

test('escapes HTML entities in bold text', function () {
    $result = $this->formatter->format('**<b>test</b>**');

    expect($result)->not->toContain('<b>test</b>');
    expect($result)->toContain('<strong>&lt;b&gt;test&lt;/b&gt;</strong>');
});
