<?php

declare(strict_types=1);

test('leaked APP_KEY from issue #3 is not present in any scanned file', function () {
    $leakedFragment = 'REP7MlRSUjsyk6kNFPjcWKc5qVZEEs98TN892ZJO0tQ';

    $candidates = [
        '/repo-root/docker-compose.yml',
        '/repo-root/README.md',
        '/repo-root/CLAUDE.md',
        base_path('.env.example'),
    ];

    $scanned = [];
    $offenders = [];
    foreach ($candidates as $path) {
        if (!is_file($path) || !is_readable($path)) {
            continue;
        }
        $scanned[] = $path;
        $contents = file_get_contents($path);
        if ($contents !== false && str_contains($contents, $leakedFragment)) {
            $offenders[] = $path;
        }
    }

    expect($scanned)->not->toBe(
        [],
        'Keine Scan-Kandidaten gefunden — Mount /repo-root fehlt moeglicherweise.'
    );

    expect($offenders)->toBe(
        [],
        'Geleakter APP_KEY-Fragment aus Issue #3 in getrackten Dateien gefunden: '
        . implode(', ', $offenders)
    );
});
