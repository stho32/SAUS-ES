<?php
declare(strict_types=1);

namespace App\Services;

class CommentFormatter
{
    public function format(string $text): string
    {
        // First, escape HTML to prevent XSS
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Bold: **text** → <strong>text</strong>
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        // Italic: *text* → <em>text</em>
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

        // Checkboxes: [x] → checked icon, [ ] → unchecked icon
        $text = str_replace('[x]', '<i class="bi bi-check-square text-success"></i>', $text);
        $text = str_replace('[ ]', '<i class="bi bi-square"></i>', $text);

        // URLs: Convert http/https URLs to clickable links
        $text = preg_replace(
            '#(https?://[^\s<]+)#',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            $text
        );

        // Preserve line breaks
        $text = nl2br($text);

        return $text;
    }
}
