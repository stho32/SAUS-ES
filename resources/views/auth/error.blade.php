<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>SAUS-ES - Fehler</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                <i class="bi bi-exclamation-triangle text-3xl text-red-600"></i>
            </div>

            <h1 class="text-2xl font-bold text-red-600 mb-2">Fehler</h1>

            <p class="text-gray-700 mb-6">
                @switch($type ?? 'default')
                    @case('unauthorized')
                        Kein gueltiger Zugangslink. Bitte verwenden Sie einen gueltigen Master-Link.
                        @break
                    @case('invalid_partner')
                        Ungueltiger Partner-Link. Der angegebene Link ist nicht gueltig oder abgelaufen.
                        @break
                    @default
                        Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.
                @endswitch
            </p>

            @if(isset($message) && $message)
                <p class="text-gray-500 text-sm mb-6">{{ $message }}</p>
            @endif

            <a href="{{ url('/') }}"
               class="inline-block bg-indigo-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition">
                <i class="bi bi-house-door mr-1"></i> Zur Startseite
            </a>
        </div>
    </div>
</body>
</html>
