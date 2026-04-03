<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#4f46e5">
    <title>SAUS-ES - @yield('title', 'Oeffentliche Ansicht')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    @yield('styles')
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    {{-- Header --}}
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <a href="{{ url('/public') }}" class="text-xl font-bold text-indigo-600">
                    SAUS-ES
                </a>
                <nav class="flex items-center space-x-4">
                    <a href="{{ url('/public') }}"
                       class="text-gray-700 hover:text-indigo-600 px-3 py-2 text-sm font-medium transition {{ request()->is('public') ? 'text-indigo-600 border-b-2 border-indigo-600' : '' }}">
                        <i class="bi bi-list-ul"></i> Tickets
                    </a>
                    <a href="{{ url('/public/news') }}"
                       class="text-gray-700 hover:text-indigo-600 px-3 py-2 text-sm font-medium transition {{ request()->is('public/news*') ? 'text-indigo-600 border-b-2 border-indigo-600' : '' }}">
                        <i class="bi bi-megaphone"></i> News
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

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            SAUS-ES &mdash; Ticket-System
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
