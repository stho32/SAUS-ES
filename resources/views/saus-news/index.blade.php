@extends('layouts.app')

@section('title', 'SAUS-News')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">SAUS-News</h1>
        <p class="text-sm text-gray-500">
            <i class="bi bi-info-circle mr-1"></i>
            Dieser Bericht dient als Datengrundlage fuer die SAUS-News und zeigt alle Ticket-Aktivitaeten im gewaehlten Zeitraum.
        </p>
    </div>
    <a href="{{ url('/') }}" class="inline-flex items-center border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
        <i class="bi bi-arrow-left mr-2"></i> Zurueck
    </a>
</div>

{{-- Date Range Selector --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('saus-news.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-5">
            <label for="from" class="block text-sm font-medium text-gray-700 mb-1">Von:</label>
            <input type="date" id="from" name="from"
                   value="{{ $from->format('Y-m-d') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
        </div>
        <div class="md:col-span-5">
            <label for="to" class="block text-sm font-medium text-gray-700 mb-1">Bis:</label>
            <input type="date" id="to" name="to"
                   value="{{ $to->format('Y-m-d') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
        </div>
        <div class="md:col-span-2 flex items-end">
            <button type="submit" class="w-full bg-brand-500 text-white px-4 py-2 rounded-lg hover:bg-brand-600 transition text-sm">
                <i class="bi bi-search mr-1"></i> Anzeigen
            </button>
        </div>
    </form>
</div>

{{-- Results --}}
@if($ticketGroups->isEmpty())
    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
        <i class="bi bi-info-circle mr-2"></i>
        Keine Aktivitaeten im gewaehlten Zeitraum gefunden
        ({{ $from->format('d.m.Y') }} &ndash; {{ $to->format('d.m.Y') }}).
    </div>
@else
    <div class="text-sm text-gray-500 mb-4">
        {{ $ticketGroups->count() }} Ticket{{ $ticketGroups->count() !== 1 ? 's' : '' }} mit Aktivitaet
        im Zeitraum {{ $from->format('d.m.Y') }} &ndash; {{ $to->format('d.m.Y') }}
    </div>

    @foreach($ticketGroups as $group)
        @php
            $ticket = $group['ticket'];
            $comments = $group['comments'];
        @endphp
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
            {{-- Ticket Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-base font-semibold text-gray-900">
                    <a href="{{ route('tickets.show', $ticket) }}" class="text-brand-500 hover:text-brand-800 transition">
                        #{{ $ticket->ticket_number ?? $ticket->id }} &mdash; {{ $ticket->title }}
                    </a>
                </h2>
                @if($ticket->status)
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                          style="background-color: {{ $ticket->status->background_color }}; color: #000;">
                        {{ $ticket->status->name }}
                    </span>
                @endif
            </div>

            {{-- Comments --}}
            <div class="px-6 py-4">
                <h3 class="text-sm font-medium text-gray-700 border-b border-gray-100 pb-2 mb-3">
                    Kommentare im Zeitraum ({{ $comments->count() }}):
                </h3>

                @foreach($comments as $comment)
                    <div class="mb-4 last:mb-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold text-gray-900">{{ $comment->username }}</span>
                            <span class="text-xs text-gray-500">{{ $comment->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="text-sm text-gray-700 pl-0 border-l-2 border-brand-200 pl-3">
                            {!! nl2br(e($comment->content)) !!}
                        </div>
                        @if(!$loop->last)
                            <hr class="mt-3 border-gray-100">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif
@endsection
