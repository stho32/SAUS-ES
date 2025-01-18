<?php
declare(strict_types=1);

/**
 * Formatiert einen Kommentartext mit Markdown-ähnlicher Syntax
 * 
 * @param string $text Der zu formatierende Text
 * @return string Der formatierte HTML-Text
 */
function formatComment(string $text): string {
    // Konvertiere Zeilenumbrüche zu <br>
    $text = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    
    // Ersetze **text** mit <strong>text</strong>
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    
    // Ersetze *text* mit <em>text</em>
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
    
    // Ersetze [x] und [ ] mit Bootstrap Icons
    $text = preg_replace('/\[x\]/i', '<i class="bi bi-check-square-fill fs-5"></i>', $text);
    $text = preg_replace('/\[ \]/i', '<i class="bi bi-square fs-5"></i>', $text);
    
    // URLs in Links umwandeln
    $text = preg_replace(
        '/(https?:\/\/[^\s<]+)/',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $text
    );
    
    return $text;
}
