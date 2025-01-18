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
    
    // Ersetze [x] und [ ] mit Checkboxen
    $text = preg_replace('/\[x\]/i', '☒', $text);
    $text = preg_replace('/\[ \]/i', '☐', $text);
    
    return $text;
}
