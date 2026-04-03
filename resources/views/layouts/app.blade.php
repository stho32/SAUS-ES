<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#4f46e5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAUS-ES - @yield('title', 'Ticket-System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Activity color classes: green -> yellow -> red gradient */
        .activity-0 { background-color: #e6ffe6 !important; }
        .activity-1 { background-color: #e8ffe3 !important; }
        .activity-2 { background-color: #ebffe0 !important; }
        .activity-3 { background-color: #edffdd !important; }
        .activity-4 { background-color: #f0ffda !important; }
        .activity-5 { background-color: #f2ffd7 !important; }
        .activity-6 { background-color: #f5ffd4 !important; }
        .activity-7 { background-color: #f7ffd1 !important; }
        .activity-8 { background-color: #fafcce !important; }
        .activity-9 { background-color: #fcf9cb !important; }
        .activity-10 { background-color: #fff6c8 !important; }
        .activity-11 { background-color: #fff3c5 !important; }
        .activity-12 { background-color: #fff0c2 !important; }
        .activity-13 { background-color: #ffedbf !important; }
        .activity-14 { background-color: #ffeabc !important; }
        .activity-old { background-color: #ffe6e6 !important; }

        /* Comment styling */
        .comment { border-left: 3px solid #4f46e5; }
        .comment-hidden { opacity: 0.5; }
        .comment-system { border-left-color: #9ca3af; font-style: italic; }

        /* Vote button styling */
        .vote-btn { min-width: 44px; min-height: 44px; }
    </style>
    @yield('styles')
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    {{-- Navigation --}}
    <nav class="bg-indigo-600 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Brand --}}
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="text-white font-bold text-xl">SAUS-ES</a>
                </div>

                {{-- Desktop Navigation --}}
                <div class="hidden lg:flex lg:items-center lg:space-x-1">
                    <a href="{{ url('/') }}"
                       class="text-white px-3 py-2 text-sm font-medium hover:bg-indigo-700 rounded transition {{ request()->is('/') ? 'bg-indigo-700 rounded' : '' }}">
                        <i class="bi bi-list-ul"></i> Uebersicht
                    </a>
                    <a href="{{ url('/follow-up') }}"
                       class="text-white px-3 py-2 text-sm font-medium hover:bg-indigo-700 rounded transition {{ request()->is('follow-up') ? 'bg-indigo-700 rounded' : '' }}">
                        <i class="bi bi-calendar-check"></i> Wiedervorlage
                    </a>
                    <a href="{{ url('/website-view') }}"
                       class="text-white px-3 py-2 text-sm font-medium hover:bg-indigo-700 rounded transition {{ request()->is('website-view') ? 'bg-indigo-700 rounded' : '' }}">
                        <i class="bi bi-globe"></i> Webseite
                    </a>
                    <a href="{{ url('/news') }}"
                       class="text-white px-3 py-2 text-sm font-medium hover:bg-indigo-700 rounded transition {{ request()->is('news*') ? 'bg-indigo-700 rounded' : '' }}">
                        <i class="bi bi-megaphone"></i> News
                    </a>
                    <a href="{{ url('/statistics') }}"
                       class="text-white px-3 py-2 text-sm font-medium hover:bg-indigo-700 rounded transition {{ request()->is('statistics') ? 'bg-indigo-700 rounded' : '' }}">
                        <i class="bi bi-graph-up"></i> Statistik
                    </a>
                    <a href="{{ url('/saus-news') }}"
                       class="text-white px-3 py-2 text-sm font-medium hover:bg-indigo-700 rounded transition {{ request()->is('saus-news') ? 'bg-indigo-700 rounded' : '' }}">
                        <i class="bi bi-newspaper"></i> SAUS-News
                    </a>
                    <a href="{{ url('/contact-persons') }}"
                       class="text-white px-3 py-2 text-sm font-medium hover:bg-indigo-700 rounded transition {{ request()->is('contact-persons') ? 'bg-indigo-700 rounded' : '' }}">
                        <i class="bi bi-people"></i> Ansprechpartner
                    </a>

                    {{-- New Ticket Button --}}
                    <a href="{{ route('tickets.create') }}"
                       class="ml-2 bg-white text-indigo-600 px-4 py-2 rounded text-sm font-semibold hover:bg-gray-100 transition">
                        <i class="bi bi-plus-lg"></i> Neues Ticket
                    </a>
                </div>

                {{-- User Display & Logout (Desktop) --}}
                <div class="hidden lg:flex lg:items-center lg:space-x-3">
                    @if(session('username'))
                        <span class="text-indigo-100 text-sm">
                            <i class="bi bi-person"></i> {{ session('username') }}
                        </span>
                        <a href="{{ route('logout') }}" class="text-white border border-white/50 px-3 py-1.5 rounded text-sm hover:bg-indigo-700 transition">
                            <i class="bi bi-box-arrow-right"></i> Abmelden
                        </a>
                    @endif
                </div>

                {{-- Mobile Hamburger Button --}}
                <div class="lg:hidden">
                    <button type="button" id="mobile-menu-btn"
                            class="text-white hover:bg-indigo-700 p-2 rounded focus:outline-none focus:ring-2 focus:ring-white"
                            aria-expanded="false">
                        <i class="bi bi-list text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Menu --}}
        <div id="mobile-menu" class="hidden lg:hidden bg-indigo-700">
            <div class="px-4 pt-2 pb-4 space-y-1">
                <a href="{{ route('tickets.create') }}"
                   class="block bg-white text-indigo-600 px-3 py-2 rounded text-sm font-semibold text-center mb-2">
                    <i class="bi bi-plus-lg"></i> Neues Ticket
                </a>
                <a href="{{ url('/') }}"
                   class="block text-white px-3 py-2 text-sm rounded hover:bg-indigo-800 {{ request()->is('/') ? 'bg-indigo-800' : '' }}">
                    <i class="bi bi-list-ul"></i> Uebersicht
                </a>
                <a href="{{ url('/follow-up') }}"
                   class="block text-white px-3 py-2 text-sm rounded hover:bg-indigo-800 {{ request()->is('follow-up') ? 'bg-indigo-800' : '' }}">
                    <i class="bi bi-calendar-check"></i> Wiedervorlage
                </a>
                <a href="{{ url('/website-view') }}"
                   class="block text-white px-3 py-2 text-sm rounded hover:bg-indigo-800 {{ request()->is('website-view') ? 'bg-indigo-800' : '' }}">
                    <i class="bi bi-globe"></i> Webseite
                </a>
                <a href="{{ url('/news') }}"
                   class="block text-white px-3 py-2 text-sm rounded hover:bg-indigo-800 {{ request()->is('news*') ? 'bg-indigo-800' : '' }}">
                    <i class="bi bi-megaphone"></i> News
                </a>
                <a href="{{ url('/statistics') }}"
                   class="block text-white px-3 py-2 text-sm rounded hover:bg-indigo-800 {{ request()->is('statistics') ? 'bg-indigo-800' : '' }}">
                    <i class="bi bi-graph-up"></i> Statistik
                </a>
                <a href="{{ url('/saus-news') }}"
                   class="block text-white px-3 py-2 text-sm rounded hover:bg-indigo-800 {{ request()->is('saus-news') ? 'bg-indigo-800' : '' }}">
                    <i class="bi bi-newspaper"></i> SAUS-News
                </a>
                <a href="{{ url('/contact-persons') }}"
                   class="block text-white px-3 py-2 text-sm rounded hover:bg-indigo-800 {{ request()->is('contact-persons') ? 'bg-indigo-800' : '' }}">
                    <i class="bi bi-people"></i> Ansprechpartner
                </a>

                @if(session('username'))
                    <div class="border-t border-indigo-600 mt-2 pt-2">
                        <span class="block text-indigo-200 px-3 py-1 text-sm">
                            <i class="bi bi-person"></i> {{ session('username') }}
                        </span>
                        <a href="{{ route('logout') }}"
                           class="block text-white px-3 py-2 text-sm rounded hover:bg-indigo-800">
                            <i class="bi bi-box-arrow-right"></i> Abmelden
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        @if(session('success'))
            <div class="bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded mb-4 flex items-center justify-between" role="alert">
                <div>
                    <i class="bi bi-check-circle-fill mr-2"></i>
                    {{ session('success') }}
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded mb-4 flex items-center justify-between" role="alert">
                <div>
                    <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                    {{ session('error') }}
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                <ul class="list-disc list-inside mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Main Content --}}
    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            @yield('content')
        </div>
    </main>

    {{-- Mobile Menu Toggle Script --}}
    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            const isHidden = menu.classList.contains('hidden');
            menu.classList.toggle('hidden');
            this.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
    </script>

    @yield('scripts')
</body>
</html>
