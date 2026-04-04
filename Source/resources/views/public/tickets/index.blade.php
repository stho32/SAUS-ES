@extends('layouts.public')

@section('title', 'Aktuelle Vorgänge')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Aktuelle Vorgänge</h1>

{{-- Search and Filter --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('public.tickets.index') }}" id="filterForm" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            {{-- Sort --}}
            <div class="md:col-span-3">
                <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="bi bi-sort-down"></i> Sortierung
                </label>
                <select id="sort" name="sort" onchange="document.getElementById('filterForm').submit()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm">
                    <option value="last_activity" {{ ($sortBy ?? 'last_activity') === 'last_activity' ? 'selected' : '' }}>Letzte Aktivitaet</option>
                    <option value="title" {{ ($sortBy ?? '') === 'title' ? 'selected' : '' }}>Titel</option>
                    <option value="id" {{ ($sortBy ?? '') === 'id' ? 'selected' : '' }}>Vorgangs-Nr</option>
                    <option value="status" {{ ($sortBy ?? '') === 'status' ? 'selected' : '' }}>Status</option>
                </select>
            </div>

            {{-- Search --}}
            <div class="md:col-span-5">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="bi bi-search"></i> Suche
                </label>
                <input type="text" id="search" name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="Suchbegriff eingeben..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm">
            </div>

            {{-- Inactive Toggle --}}
            <div class="md:col-span-4 flex items-end gap-3">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="show_all" value="1"
                           {{ ($showAll ?? false) ? 'checked' : '' }}
                           onchange="document.getElementById('filterForm').submit()"
                           class="w-4 h-4 text-brand-500 border-gray-300 rounded focus:ring-brand-500">
                    <span class="ml-2 text-sm text-gray-700">Inaktive anzeigen</span>
                </label>

                <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg hover:bg-brand-600 transition text-sm">
                    <i class="bi bi-filter"></i> Anwenden
                </button>

                @if(($search ?? '') !== '' || ($sortBy ?? 'last_activity') !== 'last_activity' || ($showAll ?? false))
                    <a href="{{ route('public.tickets.index') }}" class="border border-gray-300 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </div>

        {{-- Preserve sort direction --}}
        @if(request('dir'))
            <input type="hidden" name="dir" value="{{ request('dir') }}">
        @endif
    </form>
</div>

@if($search)
    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-2 rounded-lg mb-4 text-sm">
        <i class="bi bi-info-circle mr-1"></i>
        Suchergebnisse fuer: <strong>{{ $search }}</strong>
        <a href="{{ route('public.tickets.index', array_filter(['sort' => $sortBy, 'show_all' => $showAll ? '1' : null])) }}"
           class="ml-2 text-blue-600 hover:underline">Zurücksetzen</a>
    </div>
@endif

{{-- Ticket List --}}
@if($tickets->isEmpty())
    <div class="bg-white rounded-lg shadow text-center py-12">
        <i class="bi bi-info-circle text-5xl text-gray-300"></i>
        <h3 class="text-gray-600 mt-3 font-medium">Keine Vorgänge gefunden</h3>
        @if($search)
            <p class="text-gray-400 text-sm mt-1">Versuchen Sie es mit einem anderen Suchbegriff.</p>
        @else
            <p class="text-gray-400 text-sm mt-1">Aktuell sind keine öffentlichen Vorgänge verfügbar.</p>
        @endif
    </div>
@else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold w-24">Nr</th>
                        <th class="px-4 py-3 text-left font-semibold">Titel</th>
                        <th class="px-4 py-3 text-left font-semibold w-28">Status</th>
                        <th class="px-4 py-3 text-left font-semibold w-28">Zuständig</th>
                        <th class="px-4 py-3 text-center font-semibold w-20">Stimmen</th>
                        <th class="px-4 py-3 text-left font-semibold">Oeffentl. Kommentar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($tickets as $ticket)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <span class="text-brand-500 font-medium">#{{ $ticket->ticket_number ?? $ticket->id }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $ticket->title }}
                            </td>
                            <td class="px-4 py-3">
                                @if($ticket->status)
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                                          style="background-color: {{ $ticket->status->background_color }}; color: #000;">
                                        {{ $ticket->status->name }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                {{ $ticket->assignee ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(($ticket->up_votes_count ?? 0) > 0 || ($ticket->down_votes_count ?? 0) > 0)
                                    @if(($ticket->up_votes_count ?? 0) > 0)
                                        {{ $ticket->up_votes_count }}<i class="bi bi-hand-thumbs-up-fill text-green-600 ml-0.5"></i>
                                    @endif
                                    @if(($ticket->up_votes_count ?? 0) > 0 && ($ticket->down_votes_count ?? 0) > 0)
                                        /
                                    @endif
                                    @if(($ticket->down_votes_count ?? 0) > 0)
                                        {{ $ticket->down_votes_count }}<i class="bi bi-hand-thumbs-down-fill text-red-600 ml-0.5"></i>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                @if($ticket->public_comment)
                                    {{ Str::limit(strip_tags($ticket->public_comment), 100) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tickets->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
@endif
@endsection
