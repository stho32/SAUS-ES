@extends('layouts.app')

@section('title', 'Ticket-Uebersicht')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Ticket-Uebersicht</h1>
        <p class="text-gray-500 text-sm mt-1">
            Brauchen Sie Hilfe?
            <a href="https://chatgpt.com/g/g-AYCDjxFTR-saus" target="_blank" class="text-indigo-600 hover:underline">
                <i class="bi bi-robot"></i> SAUS-Berater-GPT
            </a>
        </p>
    </div>
    <a href="{{ route('tickets.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition text-sm">
        <i class="bi bi-plus-lg"></i> Neues Ticket
    </a>
</div>

{{-- Filter & Search --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('tickets.index') }}">
        <input type="hidden" name="filter_applied" value="1">

        {{-- Search Input --}}
        <div class="mb-4">
            <div class="relative">
                <input type="text"
                       name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="Suche in Tickets..."
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-indigo-600">
                    <i class="bi bi-arrow-right-circle"></i>
                </button>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Filter</label>
            <div class="flex flex-wrap gap-2">
                @php
                    $categoryLabels = [
                        'in_bearbeitung' => 'In Bearbeitung',
                        'zurueckgestellt' => 'Zurueckgestellt',
                        'ready' => 'Bereit zur Vorstellung',
                        'geschlossen' => 'Geschlossen',
                        'archiviert' => 'Archiviert',
                    ];
                @endphp
                @foreach($filterCategories ?? [] as $category)
                    @php
                        $isSelected = in_array($category['filter_category'], $selectedCategories ?? []);
                        $toggledCategories = $isSelected
                            ? array_diff($selectedCategories ?? [], [$category['filter_category']])
                            : array_merge($selectedCategories ?? [], [$category['filter_category']]);
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['filter_applied' => '1', 'category' => array_values($toggledCategories)]) }}"
                       class="px-3 py-1.5 rounded-full text-sm font-medium border transition
                              {{ $isSelected
                                  ? 'bg-indigo-600 text-white border-indigo-600'
                                  : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400 hover:text-indigo-600' }}">
                        {{ $categoryLabels[$category['filter_category']] ?? $category['filter_category'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Sort Dropdown --}}
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="sort" class="text-sm font-medium text-gray-700">Sortierung:</label>
                <select name="sort" id="sort" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <option value="last_activity" {{ ($sort ?? 'last_activity') === 'last_activity' ? 'selected' : '' }}>Letzte Aktivitaet</option>
                    <option value="id" {{ ($sort ?? '') === 'id' ? 'selected' : '' }}>ID</option>
                    <option value="title" {{ ($sort ?? '') === 'title' ? 'selected' : '' }}>Titel</option>
                    <option value="status" {{ ($sort ?? '') === 'status' ? 'selected' : '' }}>Status</option>
                    <option value="votes" {{ ($sort ?? '') === 'votes' ? 'selected' : '' }}>Stimmen</option>
                    <option value="affected_neighbors" {{ ($sort ?? '') === 'affected_neighbors' ? 'selected' : '' }}>Betroffene Nachbarn</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <select name="order" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <option value="desc" {{ ($order ?? 'desc') === 'desc' ? 'selected' : '' }}>Absteigend</option>
                    <option value="asc" {{ ($order ?? '') === 'asc' ? 'selected' : '' }}>Aufsteigend</option>
                </select>
            </div>
            @foreach($selectedCategories ?? [] as $cat)
                <input type="hidden" name="category[]" value="{{ $cat }}">
            @endforeach
            <a href="{{ route('tickets.index', ['filter_applied' => '1', 'category' => ['in_bearbeitung']]) }}"
               class="text-sm text-gray-500 hover:text-indigo-600 transition">
                <i class="bi bi-x-lg"></i> Filter zuruecksetzen
            </a>
        </div>
    </form>
</div>

{{-- Ticket Table --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <th class="px-4 py-3 w-24">Nr.</th>
                    <th class="px-4 py-3 w-10"></th>
                    <th class="px-4 py-3">Titel</th>
                    <th class="px-4 py-3 w-32">Status</th>
                    <th class="px-4 py-3 w-28 text-center">Stimmen</th>
                    <th class="px-4 py-3 w-16 text-center" title="Betroffene Nachbarn">ABN</th>
                    <th class="px-4 py-3 w-36">Aktivitaet</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tickets as $ticket)
                    @php
                        $activityClass = ($ticket['filter_category'] ?? '') === 'in_bearbeitung'
                            ? ($ticket['activity_class'] ?? '')
                            : '';
                    @endphp
                    <tr class="hover:bg-gray-50 cursor-pointer transition {{ $activityClass }}"
                        onclick="window.location='{{ route('tickets.show', $ticket['id']) }}'">

                        {{-- Ticket Number --}}
                        <td class="px-4 py-3 {{ $activityClass }}">
                            <a href="{{ route('tickets.show', $ticket['id']) }}" class="text-indigo-600 hover:underline font-medium">
                                #{{ $ticket['id'] }}
                            </a>
                            <a href="{{ route('tickets.show', $ticket['id']) }}" target="_blank"
                               class="ml-1 text-gray-400 hover:text-indigo-600" onclick="event.stopPropagation();" title="In neuem Tab oeffnen">
                                <i class="bi bi-box-arrow-up-right text-xs"></i>
                            </a>
                        </td>

                        {{-- Website Icon --}}
                        <td class="px-2 py-3 text-center {{ $activityClass }}">
                            @if($ticket['show_on_website'] ?? false)
                                <i class="bi bi-globe text-indigo-500" title="Oeffentlich auf der Website sichtbar"></i>
                            @endif
                        </td>

                        {{-- Title & Details --}}
                        <td class="px-4 py-3 {{ $activityClass }}">
                            <div>
                                <a href="{{ route('tickets.show', $ticket['id']) }}" class="text-gray-900 hover:text-indigo-600 font-medium">
                                    {{ $ticket['title'] }}
                                </a>
                            </div>
                            <div class="text-xs mt-1 space-y-0.5">
                                @if(!empty($ticket['assignee']))
                                    <div class="text-gray-700">Zustaendig: <span class="font-semibold">{{ $ticket['assignee'] }}</span></div>
                                @endif
                                @php
                                    $participants = [];
                                    if (!empty($ticket['last_commenter'])) $participants[] = $ticket['last_commenter'];
                                    if (!empty($ticket['other_participants'])) {
                                        $participants = array_merge($participants, explode(',', $ticket['other_participants']));
                                    }
                                    $participants = array_unique($participants);
                                @endphp
                                @if(!empty($participants))
                                    <div class="text-gray-500">
                                        Teilnehmer: {{ implode(', ', $participants) }}
                                        @if(($ticket['comment_count'] ?? 0) > 0)
                                            <span class="ml-2">({{ $ticket['comment_count'] }} {{ $ticket['comment_count'] == 1 ? 'Kommentar' : 'Kommentare' }})</span>
                                        @endif
                                    </div>
                                @endif
                                @if(!empty($ticket['contacts_genossenschaft']))
                                    <div class="text-indigo-600">
                                        + {{ $ticket['contacts_genossenschaft'] }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        {{-- Status Badge --}}
                        <td class="px-4 py-3 {{ $activityClass }}">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium"
                                  style="background-color: {{ $ticket['background_color'] ?? '#e5e7eb' }}; color: #000;">
                                {{ $ticket['status_name'] }}
                            </span>
                        </td>

                        {{-- Votes --}}
                        <td class="px-4 py-3 text-center {{ $activityClass }}">
                            @if(($ticket['up_votes'] ?? 0) > 0 || ($ticket['down_votes'] ?? 0) > 0)
                                @if(($ticket['up_votes'] ?? 0) > 0)
                                    <span class="text-green-600">{{ $ticket['up_votes'] }}<i class="bi bi-hand-thumbs-up-fill ml-0.5"></i></span>
                                @endif
                                @if(($ticket['up_votes'] ?? 0) > 0 && ($ticket['down_votes'] ?? 0) > 0)
                                    <span class="text-gray-400 mx-0.5">/</span>
                                @endif
                                @if(($ticket['down_votes'] ?? 0) > 0)
                                    <span class="text-red-600">{{ $ticket['down_votes'] }}<i class="bi bi-hand-thumbs-down-fill ml-0.5"></i></span>
                                @endif
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        {{-- Affected Neighbors --}}
                        <td class="px-4 py-3 text-center {{ $activityClass }}">
                            {{ $ticket['affected_neighbors'] !== null ? (int)$ticket['affected_neighbors'] : '-' }}
                        </td>

                        {{-- Last Activity --}}
                        <td class="px-4 py-3 text-sm text-gray-600 {{ $activityClass }}">
                            {{ \Carbon\Carbon::parse($ticket['last_activity'])->format('d.m.Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center">
                            <div class="bg-blue-50 text-blue-700 rounded-lg px-4 py-3 inline-block">
                                <i class="bi bi-info-circle mr-1"></i>
                                Keine Tickets gefunden. Passen Sie die Filter an oder erstellen Sie ein neues Ticket.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!empty($tickets) && count($tickets) > 0)
    <div class="text-right text-gray-500 text-sm mt-2">
        {{ count($tickets) }} Ticket{{ count($tickets) !== 1 ? 's' : '' }} angezeigt
        @if(!empty($selectedCategories))
            (gefiltert)
        @endif
    </div>
@endif
@endsection
