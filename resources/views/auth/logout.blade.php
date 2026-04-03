<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>SAUS-ES - Abgemeldet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
                <i class="bi bi-hand-wave text-3xl text-indigo-600"></i>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Auf Wiedersehen!</h1>

            <p class="text-gray-600 mb-6">
                Sie wurden erfolgreich abgemeldet.<br>
                Danke, dass Sie da waren!
            </p>

            <a href="{{ url('/') }}"
               class="inline-block bg-indigo-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition">
                <i class="bi bi-house-door mr-1"></i> Zurueck zur Startseite
            </a>
        </div>
    </div>
</body>
</html>
