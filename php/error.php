<?php
$errorType = $_GET['type'] ?? 'unknown';

$errorMessages = [
    'unauthorized' => 'Sie haben keine Berechtigung für diese Seite. Bitte verwenden Sie einen gültigen Master-Link.',
    'invalid_partner' => 'Ungültiger Partner-Link.',
    'unknown' => 'Ein unbekannter Fehler ist aufgetreten.'
];

$errorMessage = $errorMessages[$errorType] ?? $errorMessages['unknown'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUS-ES - Fehler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title text-danger">Fehler</h3>
                        <p class="card-text"><?= htmlspecialchars($errorMessage) ?></p>
                        <a href="index.php" class="btn btn-primary">Zur Startseite</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
