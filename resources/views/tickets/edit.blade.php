@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_number . ' bearbeiten')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Ticket bearbeiten</h1>
        <span class="text-gray-500 text-sm">{{ $ticket->ticket_number }}</span>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('tickets.show', $ticket) }}"
           class="text-gray-600 hover:text-gray-800 border border-gray-300 bg-white px-4 py-2 rounded-lg text-sm font-medium transition">
            <i class="bi bi-x-lg"></i> Abbrechen
        </a>
        <button type="button" id="saveButton" onclick="updateTicket()"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
            <i class="bi bi-check-lg"></i> Speichern
        </button>
    </div>
</div>

<input type="hidden" id="ticketId" value="{{ $ticket->id }}">

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left Column: Main Content --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h5 class="font-semibold text-gray-900 mb-4">Allgemeine Informationen</h5>

            {{-- Ticket Number (read-only) --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ticket-Nummer</label>
                <input type="text" value="{{ $ticket->ticket_number }}" readonly
                       class="w-full border border-gray-200 rounded-lg px-4 py-2.5 bg-gray-50 text-gray-500 cursor-not-allowed">
                <p class="text-xs text-gray-500 mt-1">Die Ticket-Nummer kann nicht geaendert werden.</p>
            </div>

            {{-- Title --}}
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                <input type="text" id="title" value="{{ $ticket->title }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            {{-- Description --}}
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                <textarea id="description" rows="5" required
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">{{ $ticket->description }}</textarea>
            </div>

            {{-- Assignee --}}
            <div class="mb-4">
                <label for="assignee" class="block text-sm font-medium text-gray-700 mb-1">Zustaendige Bearbeiter</label>
                <input type="text" id="assignee" value="{{ $ticket->assignee ?? '' }}" maxlength="200"
                       placeholder="Namen der zustaendigen Bearbeiter"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            {{-- Affected Neighbors --}}
            <div class="mb-4">
                <label for="affectedNeighbors" class="block text-sm font-medium text-gray-700 mb-1">Anzahl betroffener Nachbarn</label>
                <input type="number" id="affectedNeighbors" min="0"
                       value="{{ $ticket->affected_neighbors !== null ? $ticket->affected_neighbors : '' }}"
                       placeholder="Anzahl der betroffenen Nachbarn"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                <p class="text-xs text-gray-500 mt-1">Leer lassen wenn unbekannt.</p>
            </div>

            {{-- KI Summary (read-only) --}}
            @if($ticket->ki_summary)
                <div class="bg-gray-50 border-l-4 border-green-500 rounded-r-lg p-4 mb-4">
                    <h6 class="text-sm font-medium text-gray-500 mb-1">
                        <i class="bi bi-robot text-green-600"></i> KI-Zusammenfassung
                    </h6>
                    <p class="text-gray-700 text-sm whitespace-pre-line">{{ $ticket->ki_summary }}</p>
                </div>
            @endif

            {{-- KI Interim (read-only) --}}
            @if($ticket->ki_interim)
                <div class="bg-gray-50 border-l-4 border-blue-400 rounded-r-lg p-4 mb-4">
                    <h6 class="text-sm font-medium text-gray-500 mb-1">
                        <i class="bi bi-robot text-blue-500"></i> KI-Zwischenstand
                    </h6>
                    <p class="text-gray-700 text-sm whitespace-pre-line">{{ $ticket->ki_interim }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Right Column: Status & Metadata --}}
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h5 class="font-semibold text-gray-900 mb-4">Status & Metadaten</h5>

            {{-- Status --}}
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    @foreach($statuses as $status)
                        <option value="{{ $status->id }}" {{ $status->id == $ticket->status_id ? 'selected' : '' }}>
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Follow-up Date --}}
            <div class="mb-4">
                <label for="followUpDate" class="block text-sm font-medium text-gray-700 mb-1">Wiedervorlagedatum</label>
                <div class="flex gap-2">
                    <input type="date" id="followUpDate"
                           value="{{ $ticket->follow_up_date ? $ticket->follow_up_date->format('Y-m-d') : '' }}"
                           class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    <button type="button" id="clearFollowUpDate"
                            class="text-gray-500 hover:text-gray-700 border border-gray-300 px-3 py-2 rounded-lg transition" title="Datum loeschen">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Datum, an dem dieses Ticket erneut betrachtet werden sollte.</p>
            </div>

            {{-- Do Not Track --}}
            <div class="mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="doNotTrack" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           {{ $ticket->do_not_track ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Nicht verfolgen</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">Ticket wird in der Wiedervorlage-Uebersicht nicht angezeigt.</p>
            </div>

            {{-- Show on Website --}}
            <div class="mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="showOnWebsite" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           {{ $ticket->show_on_website ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Auf Website anzeigen</span>
                </label>
            </div>

            {{-- Public Comment --}}
            <div class="mb-4">
                <label for="publicComment" class="block text-sm font-medium text-gray-700 mb-1">Oeffentlicher Kommentar</label>
                <textarea id="publicComment" rows="3"
                          placeholder="Dieser Kommentar wird auf der Website angezeigt"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">{{ $ticket->public_comment ?? '' }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Dieser Text wird auf der Website neben dem Ticket-Titel angezeigt.</p>
            </div>
        </div>
    </div>
</div>

<div class="mt-6 mb-6">
    <a href="{{ route('tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">
        <i class="bi bi-arrow-left mr-1"></i> Zurueck zum Ticket
    </a>
</div>
@endsection

@section('scripts')
<script>
var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

// Clear follow-up date
document.getElementById('clearFollowUpDate').addEventListener('click', function() {
    document.getElementById('followUpDate').value = '';
});

async function updateTicket() {
    var ticketId = document.getElementById('ticketId').value;
    var data = {
        title: document.getElementById('title').value,
        description: document.getElementById('description').value,
        status_id: document.getElementById('status').value,
        assignee: document.getElementById('assignee').value,
        show_on_website: document.getElementById('showOnWebsite').checked,
        public_comment: document.getElementById('publicComment').value,
        affected_neighbors: document.getElementById('affectedNeighbors').value === '' ? null : parseInt(document.getElementById('affectedNeighbors').value),
        follow_up_date: document.getElementById('followUpDate').value || null,
        do_not_track: document.getElementById('doNotTrack').checked
    };

    var saveButton = document.getElementById('saveButton');

    try {
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></span> Speichern...';

        var response = await fetch('/api/tickets/' + ticketId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        var result = await response.json();

        if (result.success) {
            window.location.href = '/tickets/' + ticketId;
        } else {
            alert('Fehler beim Speichern: ' + (result.message || 'Unbekannter Fehler'));
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="bi bi-check-lg"></i> Speichern';
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Speichern des Tickets');
        saveButton.disabled = false;
        saveButton.innerHTML = '<i class="bi bi-check-lg"></i> Speichern';
    }
}
</script>
@endsection
