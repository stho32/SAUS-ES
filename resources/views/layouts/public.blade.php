<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#0786c0">
    <title>@yield('title', 'Oeffentliche Informationen') - Siedlungsausschuss</title>
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
    @yield('styles')
</head>
<body class="min-h-screen flex flex-col" style="background-color: #f0efe8; color: #3c3c3c;">
    {{-- Header — deliberately generic, no mention of admin tool --}}
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <a href="{{ route('public.tickets.index') }}" class="text-xl font-bold text-brand-500">
                    Siedlungsausschuss
                </a>
                <nav class="flex items-center space-x-4">
                    <a href="{{ route('public.tickets.index') }}"
                       class="text-gray-700 hover:text-brand-500 px-3 py-2 text-sm font-medium transition {{ request()->routeIs('public.tickets.*') ? 'text-brand-500 border-b-2 border-brand-500' : '' }}">
                        <i class="bi bi-list-ul"></i> Aktuelle Vorgaenge
                    </a>
                    <a href="{{ route('public.news.index') }}"
                       class="text-gray-700 hover:text-brand-500 px-3 py-2 text-sm font-medium transition {{ request()->routeIs('public.news.*') ? 'text-brand-500 border-b-2 border-brand-500' : '' }}">
                        <i class="bi bi-megaphone"></i> Neuigkeiten
                    </a>
                </nav>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            @yield('content')
        </div>
    </main>

    {{-- Footer — no links to admin area --}}
    <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            Siedlungsausschuss &mdash; Informationen fuer Mitglieder
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
