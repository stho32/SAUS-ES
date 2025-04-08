<?php
declare(strict_types=1);

/**
 * Berechnet die CSS-Klasse für die Aktivitätsanzeige basierend auf dem letzten Aktivitätsdatum
 * 
 * @param string $lastActivity Das Datum der letzten Aktivität
 * @return string Die CSS-Klasse für die Aktivitätsanzeige
 */
function getActivityClass(string $lastActivity): string {
    $lastActivityDate = new DateTime($lastActivity);
    $today = new DateTime();
    $diff = $today->diff($lastActivityDate);
    $daysDiff = (int)$diff->format('%r%a');  // Negative Zahl für Vergangenheit

    if ($daysDiff > 14) {
        return 'activity-old';
    }

    return 'activity-' . abs($daysDiff);
}
