<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAUS-i - Anmeldung</title>
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
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-100 rounded-full mb-4">
                    <i class="bi bi-person-circle text-3xl text-brand-500"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Willkommen bei SAUS-i</h1>
                <p class="text-gray-600 mt-2">Bitte geben Sie Ihr Namenskürzel ein</p>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded mb-4">
                    <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('username.store') }}">
                @csrf
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                        Namenskürzel
                    </label>
                    <input type="text"
                           id="username"
                           name="username"
                           value="{{ old('username') }}"
                           required
                           minlength="2"
                           maxlength="50"
                           pattern="[A-Za-z0-9\s\u00C0-\u024F]+"
                           placeholder="z.B. MaMu oder Max Mustermann"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition text-lg"
                           autofocus>
                    <p class="text-gray-500 text-xs mt-1">2-50 Zeichen, Buchstaben, Zahlen und Leerzeichen erlaubt.</p>
                </div>

                <button type="submit"
                        class="w-full bg-brand-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-brand-600 focus:ring-4 focus:ring-brand-300 transition text-lg">
                    Weiter
                </button>
            </form>
        </div>
    </div>
</body>
</html>
