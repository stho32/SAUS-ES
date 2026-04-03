@extends('layouts.app')

@section('title', 'Wiedervorlage')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Wiedervorlage</h1>
        <p class="text-sm text-gray-500">Zeigt Tickets, die Ihre Aufmerksamkeit benoetigen</p>
    </div>
    <a href="{{ route('tickets.create') }}"
       class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition text-sm">
        <i class="bi bi-plus-lg mr-2"></i> Neues Ticket
    </a>
</div>

{{-- Filters --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('follow-up.index') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-8">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Tickets durchsuchen..."
                           class="w-full border border-gray-300 rounded-lg pl-10 pr-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <div class="md:col-span-4 flex items-end gap-2">
                <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg hover:bg-brand-600 transition text-sm">
                    <i class="bi bi-search mr-1"></i> Suchen
                </button>
                @if($search)
                    <a href="{{ route('follow-up.index', ['filter' => $filterCategory]) }}"
                       class="border border-gray-300 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </div>

        {{-- Status category filter --}}
        <div class="flex flex-wrap gap-2">
            @foreach($statusCategories as $key => $label)
                <a href="{{ route('follow-up.index', array_merge(request()->except('filter', 'page'), ['filter' => $key])) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                          {{ $filterCategory === $key ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </form>
</div>

{{-- Ticket Table --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    @if($tickets->isEmpty())
        <div class="text-center py-12">
            <i class="bi bi-calendar-check text-5xl text-gray-300"></i>
            <p class="text-gray-500 mt-3">Keine Tickets gefunden.</p>
            @if($search || $filterCategory !== 'in_bearbeitung')
                <p class="text-gray-400 text-sm">Versuchen Sie andere Filterbedingungen.</p>
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold w-28">Ticket Nr</th>
                        <th class="px-4 py-3 text-left font-semibold">Titel</th>
                        <th class="px-4 py-3 text-left font-semibold w-28">Status</th>
                        <th class="px-4 py-3 text-left font-semibold w-32">Zustaendig</th>
                        <th class="px-4 py-3 text-center font-semibold w-36">Wiedervorlage</th>
                        <th class="px-4 py-3 text-left font-semibold w-40">Letzte Aktivitaet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($tickets as $ticket)
                        @php
                            $followUp = $ticket->follow_up_date;
                            $rowBg = '';
                            $dateColor = '';
                            $dateIcon = '';

                            if ($followUp) {
                                if ($followUp->lt(\Carbon\Carbon::parse($today))) {
                                    $rowBg = 'bg-red-50';
                                    $dateColor = 'text-red-600 font-semibold';
                                    $dateIcon = 'bi-exclamation-circle-fill text-red-500';
                                } elseif ($followUp->eq(\Carbon\Carbon::parse($today))) {
                                    $rowBg = 'bg-amber-50';
                                    $dateColor = 'text-amber-600 font-semibold';
                                    $dateIcon = 'bi-star-fill text-amber-500';
                                } else {
                                    $rowBg = '';
                                    $dateColor = 'text-green-600';
                                    $dateIcon = '';
                                }
                            }

                            $lastActivity = $ticket->last_comment_at
                                ? \Carbon\Carbon::parse($ticket->last_comment_at)
                                : $ticket->created_at;
                            $daysSinceActivity = $lastActivity ? (int) $lastActivity->diffInDays(now()) : 0;
                            $activityClass = $daysSinceActivity > 14 ? 'activity-old' : 'activity-' . min($daysSinceActivity, 14);
                        @endphp
                        <tr class="{{ $rowBg }} hover:bg-opacity-80 transition cursor-pointer"
                            onclick="window.location='{{ route('tickets.show', $ticket) }}?ref=follow-up'">
                            <td class="px-4 py-3 {{ $activityClass }}">
                                <span class="text-brand-500 font-medium">#{{ $ticket->ticket_number ?? $ticket->id }}</span>
                            </td>
                            <td class="px-4 py-3 {{ $activityClass }}">
                                <div>
                                    <a href="{{ route('tickets.show', $ticket) }}?ref=follow-up"
                                       class="text-gray-900 font-medium hover:text-brand-500 transition">
                                        {{ $ticket->title }}
                                        @if($ticket->show_on_website)
                                            <i class="bi bi-globe ml-1 text-gray-400" title="Wird auf der Website angezeigt"></i>
                                        @endif
                                    </a>
                                </div>
                                @if($ticket->assignee)
                                    <p class="text-xs text-gray-500 mt-0.5">Zustaendig: <strong>{{ $ticket->assignee }}</strong></p>
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
                            <td class="px-4 py-3 {{ $activityClass }} text-gray-600 text-xs">
                                {{ $ticket->assignee ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-center {{ $activityClass }}">
                                @if($followUp)
                                    @if($dateIcon)
                                        <i class="bi {{ $dateIcon }} mr-1"></i>
                                    @endif
                                    <span class="{{ $dateColor }}">{{ $followUp->format('d.m.Y') }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 {{ $activityClass }} text-gray-600 text-xs">
                                @if($lastActivity)
                                    {{ $lastActivity->format('d.m.Y H:i') }}
                                    <br>
                                    <span class="text-gray-400">({{ $daysSinceActivity }} {{ $daysSinceActivity === 1 ? 'Tag' : 'Tage' }})</span>
                                @else
                                    -
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

        <div class="text-right text-gray-500 text-xs px-4 py-2 border-t border-gray-100">
            {{ $tickets->total() }} Ticket{{ $tickets->total() !== 1 ? 's' : '' }} angezeigt
        </div>
    @endif
</div>
@endsection
