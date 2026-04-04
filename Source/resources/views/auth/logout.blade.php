<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>SAUS-i - Abgemeldet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        50:  "#e6f4fb",
                        100: "#cce9f7",
                        200: "#99d3ef",
                        300: "#66bde7",
                        400: "#33a7df",
                        500: "#0786c0",
                        600: "#0675a9",
                        700: "#056492",
                        800: "#04537b",
                        900: "#034264",
                    }
                }
            }
        }
    }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-100 rounded-full mb-4">
                <i class="bi bi-hand-wave text-3xl text-brand-500"></i>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Auf Wiedersehen!</h1>

            <p class="text-gray-600 mb-6">
                Sie wurden erfolgreich abgemeldet.<br>
                Danke, dass Sie da waren!
            </p>

            <a href="{{ url('/') }}"
               class="inline-block bg-brand-500 text-white py-2 px-6 rounded-lg font-semibold hover:bg-brand-600 transition">
                <i class="bi bi-house-door mr-1"></i> Zurück zur Startseite
            </a>
        </div>
    </div>
</body>
</html>
