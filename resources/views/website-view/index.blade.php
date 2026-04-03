@extends('layouts.app')

@section('title', 'Webseiten-Ansicht')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Webseiten-Ansicht</h1>
        <p class="text-sm text-gray-500">Zeigt alle Tickets, die auf der Website angezeigt werden</p>
    </div>
    <a href="{{ route('tickets.create') }}"
       class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition text-sm">
        <i class="bi bi-plus-lg mr-2"></i> Neues Ticket
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    @if($tickets->isEmpty())
        <div class="text-center py-12">
            <i class="bi bi-globe text-5xl text-gray-300"></i>
            <p class="text-gray-500 mt-3">Keine Tickets fuer die Website-Anzeige gefunden.</p>
            <p class="text-gray-400 text-sm mt-1">
                Markieren Sie Tickets fuer die Website-Anzeige in der Ticket-Bearbeitung.
            </p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Nr / Titel</th>
                        <th class="px-4 py-3 text-left font-semibold w-28">Status</th>
                        <th class="px-4 py-3 text-center font-semibold w-24">
                            <i class="bi bi-hand-thumbs-up"></i> Stimmen
                        </th>
                        <th class="px-4 py-3 text-left font-semibold">Oeffentlicher Kommentar</th>
                        <th class="px-4 py-3 text-left font-semibold w-40">Letzte Aktivitaet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($tickets as $ticket)
                        @php
                            $lastActivity = $ticket->last_activity_at
                                ? \Carbon\Carbon::parse($ticket->last_activity_at)
                                : $ticket->created_at;
                            $daysSinceActivity = $lastActivity ? (int) $lastActivity->diffInDays(now()) : 0;
                            $activityClass = $daysSinceActivity > 14 ? 'activity-old' : 'activity-' . min($daysSinceActivity, 14);
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 {{ $activityClass }}">
                                <a href="{{ route('tickets.show', $ticket) }}?ref=website-view"
                                   class="text-indigo-600 hover:text-indigo-800 font-medium transition">
                                    #{{ $ticket->ticket_number ?? $ticket->id }}: {{ $ticket->title }}
                                </a>
                                @if($ticket->assignee)
                                    <p class="text-xs text-gray-500 mt-0.5">Zustaendig: <strong>{{ $ticket->assignee }}</strong></p>
                                @endif
                                @if($ticket->comments_count > 0)
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $ticket->comments_count }} {{ $ticket->comments_count === 1 ? 'Kommentar' : 'Kommentare' }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3 {{ $activityClass }}">
                                @if($ticket->status)
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                                          style="background-color: {{ $ticket->status->background_color }}; color: #000;">
                                        {{ $ticket->status->name }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center {{ $activityClass }}">
                                @if(($ticket->up_votes_count ?? 0) > 0 || ($ticket->down_votes_count ?? 0) > 0)
                                    @if(($ticket->up_votes_count ?? 0) > 0)
                                        {{ $ticket->up_votes_count }}<i class="bi bi-hand-thumbs-up-fill text-green-600 ml-0.5"></i>
                                    @endif
                                    @if(($ticket->up_votes_count ?? 0) > 0 && ($ticket->down_votes_count ?? 0) > 0)
                                        <span class="mx-0.5">/</span>
                                    @endif
                                    @if(($ticket->down_votes_count ?? 0) > 0)
                                        {{ $ticket->down_votes_count }}<i class="bi bi-hand-thumbs-down-fill text-red-600 ml-0.5"></i>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 {{ $activityClass }}">
                                @if($ticket->public_comment)
                                    <p class="text-gray-600 text-xs line-clamp-2">{{ Str::limit(strip_tags($ticket->public_comment), 120) }}</p>
                                @else
                                    <span class="text-gray-400 text-xs">Kein oeffentlicher Kommentar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 {{ $activityClass }} text-gray-600 text-xs">
                                @if($lastActivity)
                                    {{ $lastActivity->format('d.m.Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-right text-gray-500 text-xs px-4 py-2 border-t border-gray-100">
            {{ $tickets->count() }} Ticket{{ $tickets->count() !== 1 ? 's' : '' }} angezeigt
        </div>
    @endif
</div>
@endsection
