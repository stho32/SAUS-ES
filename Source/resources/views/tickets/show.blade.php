@extends('layouts.app')

@section('title', $ticket->ticket_number . ' - ' . $ticket->title)

@section('styles')
<style>
    .comment-content a { color: #4f46e5; text-decoration: underline; }
    .comment-content a:hover { color: #3730a3; }
    .thumbnail-img { max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 0.375rem; cursor: pointer; }
    .comment-hidden { display: none; }
</style>
@endsection

@section('content')
<div class="mb-6">
    {{-- Header --}}
    <div class="mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $ticket->title }}</h1>
                <div class="text-gray-500 text-sm mt-1 flex items-center gap-2">
                    <span>{{ $ticket->ticket_number }}</span>
                    @if($ticket->status)
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold text-white"
                              style="background-color: {{ $ticket->status->background_color }}">
                            {{ $ticket->status->name }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                {{-- Voting Buttons --}}
                <div class="inline-flex rounded-lg overflow-hidden border border-gray-300" role="group" id="ticket-voting">
                    <button type="button"
                            class="vote-btn px-3 py-2 text-sm font-medium transition
                                   {{ $userTicketVote && $userTicketVote->value === 'up' ? 'bg-green-500 text-white' : 'bg-white text-green-600 hover:bg-green-50' }}"
                            onclick="voteTicket('up')"
                            title="{{ $ticket->upvoters ?: 'Keine Upvotes' }}">
                        <i class="bi bi-hand-thumbs-up"></i>
                        <span class="upvote-count">{{ $ticket->up_votes_count ?? 0 }}</span>
                    </button>
                    <button type="button"
                            class="vote-btn px-3 py-2 text-sm font-medium border-l border-gray-300 transition
                                   {{ $userTicketVote && $userTicketVote->value === 'down' ? 'bg-red-500 text-white' : 'bg-white text-red-600 hover:bg-red-50' }}"
                            onclick="voteTicket('down')"
                            title="{{ $ticket->downvoters ?: 'Keine Downvotes' }}">
                        <i class="bi bi-hand-thumbs-down"></i>
                        <span class="downvote-count">{{ $ticket->down_votes_count ?? 0 }}</span>
                    </button>
                </div>

                {{-- Action Buttons --}}
                <a href="{{ route('tickets.edit', $ticket) }}"
                   class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-600 transition">
                    <i class="bi bi-pencil"></i> Bearbeiten
                </a>
                <a href="{{ route('tickets.email', $ticket) }}" target="_blank"
                   class="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    <i class="bi bi-envelope"></i> E-Mail Ansicht
                </a>
                <a href="{{ route('tickets.index') }}"
                   class="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
            </div>
        </div>
    </div>

    {{-- Info Cards Row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Assignee Card --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="bi bi-person-circle text-3xl text-gray-400"></i>
                </div>
                <div class="ml-4">
                    <h6 class="text-xs font-medium text-gray-500 uppercase">Zuständig</h6>
                    <p class="text-lg mt-0.5">
                        <button type="button" onclick="document.getElementById('assignee-modal').classList.remove('hidden')"
                                class="text-gray-900 hover:text-brand-500 transition">
                            {{ $ticket->assignee ?: 'Nicht zugewiesen' }}
                            <i class="bi bi-pencil-square ml-1 text-sm text-gray-400"></i>
                        </button>
                    </p>
                </div>
            </div>
        </div>

        {{-- Status Card --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    @if($ticket->status)
                        <i class="bi bi-flag-fill text-3xl" style="color: {{ $ticket->status->background_color }}"></i>
                    @else
                        <i class="bi bi-flag-fill text-3xl text-gray-400"></i>
                    @endif
                </div>
                <div class="ml-4">
                    <h6 class="text-xs font-medium text-gray-500 uppercase">Status</h6>
                    <p class="text-lg mt-0.5">
                        <button type="button" onclick="document.getElementById('status-modal').classList.remove('hidden')"
                                class="text-gray-900 hover:text-brand-500 transition">
                            @if($ticket->status)
                                <span class="inline-block px-2 py-0.5 rounded text-sm font-medium"
                                      style="background-color: {{ $ticket->status->background_color }}">
                                    {{ $ticket->status->name }}
                                </span>
                            @else
                                Kein Status
                            @endif
                            <i class="bi bi-pencil-square ml-1 text-sm text-gray-400"></i>
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Second Row: Follow-up, Do-Not-Track, Affected Neighbors --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Follow-up Date --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <i class="bi bi-calendar-event text-2xl text-gray-400 flex-shrink-0"></i>
                <div class="ml-3">
                    <h6 class="text-xs font-medium text-gray-500 uppercase">Wiedervorlage</h6>
                    <p class="text-sm mt-0.5">
                        <button type="button" onclick="document.getElementById('followup-modal').classList.remove('hidden')"
                                class="text-gray-900 hover:text-brand-500 transition">
                            {{ $ticket->follow_up_date ? $ticket->follow_up_date->format('d.m.Y') : 'Kein Datum gesetzt' }}
                            <i class="bi bi-pencil-square ml-1 text-xs text-gray-400"></i>
                        </button>
                    </p>
                </div>
            </div>
        </div>

        {{-- Do-Not-Track --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <i class="bi bi-eye-slash text-2xl text-gray-400 flex-shrink-0"></i>
                <div class="ml-3">
                    <h6 class="text-xs font-medium text-gray-500 uppercase">Nicht verfolgen</h6>
                    <p class="text-sm mt-0.5">
                        @if($ticket->do_not_track)
                            <span class="text-orange-600 font-medium">Ja</span>
                        @else
                            <span class="text-gray-500">Nein</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Affected Neighbors --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <i class="bi bi-people text-2xl text-gray-400 flex-shrink-0"></i>
                <div class="ml-3">
                    <h6 class="text-xs font-medium text-gray-500 uppercase">Betroffene Nachbarn</h6>
                    <p class="text-sm mt-0.5">
                        {{ $ticket->affected_neighbors !== null ? $ticket->affected_neighbors : 'Unbekannt' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Contact Persons --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
            <h5 class="font-semibold text-gray-900">Ansprechpartner bei der Genossenschaft</h5>
            <button type="button" onclick="document.getElementById('add-contact-modal').classList.remove('hidden')"
                    class="bg-brand-500 text-white text-xs px-3 py-1.5 rounded font-medium hover:bg-brand-600 transition">
                <i class="bi bi-plus-lg"></i> Hinzufügen
            </button>
        </div>
        <div class="p-4">
            @if($ticket->contactPersons->isEmpty())
                <p class="text-gray-500 text-sm">Keine Ansprechpartner verknuepft.</p>
            @else
                <div class="space-y-2">
                    @foreach($ticket->contactPersons as $person)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg {{ !$person->is_active ? 'opacity-50' : '' }}"
                             id="cp-{{ $person->id }}">
                            <div>
                                <span class="font-medium text-gray-900">{{ $person->name }}</span>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    @if($person->email)
                                        <i class="bi bi-envelope"></i> {{ $person->email }}
                                    @endif
                                    @if($person->phone)
                                        {{ $person->email ? ' | ' : '' }}
                                        <i class="bi bi-telephone"></i> {{ $person->phone }}
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($person->responsibility_notes)
                                    <button type="button"
                                            class="text-blue-600 hover:text-blue-800 p-1"
                                            title="{{ $person->responsibility_notes }}">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                @endif
                                <button type="button"
                                        onclick="removeContactPerson({{ $person->id }}, '{{ e($person->name) }}')"
                                        class="text-red-500 hover:text-red-700 p-1" title="Entfernen">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="mt-3">
                <a href="{{ route('contact-persons.index') }}" class="text-sm text-gray-500 hover:text-brand-500 transition">
                    <i class="bi bi-gear"></i> Ansprechpartner verwalten
                </a>
            </div>
        </div>
    </div>

    {{-- KI Summary --}}
    @if($ticket->ki_summary)
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-4 mb-6">
            <h5 class="font-semibold text-gray-900 mb-2">
                <i class="bi bi-robot text-green-600"></i> KI-Zusammenfassung
            </h5>
            <p class="text-gray-700 whitespace-pre-line">{{ $ticket->ki_summary }}</p>
        </div>
    @endif

    {{-- KI Interim --}}
    @if($ticket->ki_interim)
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-400 p-4 mb-6">
            <h5 class="font-semibold text-gray-900 mb-2">
                <i class="bi bi-robot text-blue-500"></i> KI-Zwischenstand
            </h5>
            <p class="text-gray-700 whitespace-pre-line">{{ $ticket->ki_interim }}</p>
        </div>
    @endif

    {{-- Description --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <h5 class="font-semibold text-gray-900 mb-3">Beschreibung</h5>
        <div class="text-gray-700 whitespace-pre-line">{{ $ticket->description }}</div>
    </div>

    {{-- Website Info (Public Comment) --}}
    @if($ticket->show_on_website)
        <div class="bg-white rounded-lg shadow-sm border border-blue-300 mb-6">
            <div class="bg-blue-50 px-4 py-3 rounded-t-lg border-b border-blue-200">
                <h5 class="font-semibold text-blue-800">
                    <i class="bi bi-globe"></i> Website-Informationen
                </h5>
            </div>
            <div class="p-4">
                <h6 class="text-xs font-medium text-gray-500 uppercase mb-2">Öffentlicher Kommentar</h6>
                @if($ticket->public_comment)
                    <p class="text-gray-700 whitespace-pre-line">{{ $ticket->public_comment }}</p>
                @else
                    <p class="text-gray-400 italic">Kein öffentlicher Kommentar vorhanden</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Attachments Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-4 py-3 border-b border-gray-200">
            <h5 class="font-semibold text-gray-900">
                Anhaenge
                <span class="text-sm font-normal text-gray-500">({{ $ticket->attachments->count() }})</span>
            </h5>
        </div>
        <div class="p-4">
            {{-- Upload Form --}}
            <form id="uploadForm" class="mb-4">
                <div class="flex gap-2">
                    <input type="file" id="fileInput" name="file"
                           class="flex-1 text-sm text-gray-700 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-600 hover:file:bg-brand-100 cursor-pointer">
                    <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-600 transition whitespace-nowrap">
                        <i class="bi bi-upload mr-1"></i> Hochladen
                    </button>
                </div>
                <div id="uploadError" class="hidden bg-red-50 border border-red-300 text-red-800 px-3 py-2 rounded mt-2 text-sm"></div>
            </form>

            {{-- Attachment Grid --}}
            <div id="attachmentGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                @forelse($ticket->attachments as $attachment)
                    <div class="border border-gray-200 rounded-lg overflow-hidden" id="attachment-{{ $attachment->id }}">
                        @if(str_starts_with($attachment->file_type ?? '', 'image/'))
                            <a href="{{ route('api.attachments.show', $attachment) }}"
                               target="_blank" class="block">
                                <img src="{{ route('api.attachments.show', $attachment) }}"
                                     loading="lazy"
                                     alt="{{ $attachment->original_filename }}"
                                     class="w-full h-32 object-cover">
                            </a>
                        @else
                            <a href="{{ route('api.attachments.show', $attachment) }}"
                               target="_blank" class="flex items-center justify-center h-32 bg-gray-50 text-gray-400">
                                <i class="bi bi-file-earmark text-4xl"></i>
                            </a>
                        @endif
                        <div class="p-2">
                            <div class="text-xs text-gray-900 truncate" title="{{ $attachment->original_filename }}">
                                {{ $attachment->original_filename }}
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-500">
                                    {{ $attachment->uploaded_by ?? '' }} &ndash;
                                    {{ $attachment->upload_date ? $attachment->upload_date->format('d.m.Y H:i') : '' }}
                                </span>
                                <button onclick="deleteAttachment({{ $attachment->id }})"
                                        class="text-red-500 hover:text-red-700 text-xs p-1" title="Löschen">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm col-span-full">Keine Anhaenge vorhanden.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Comments Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg flex items-center justify-between">
            <h5 class="font-semibold text-gray-900">
                Kommentare
                <span class="text-sm font-normal text-gray-500">({{ $ticket->comments->count() }})</span>
            </h5>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" id="showAllComments" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    Alle anzeigen
                </label>
            </div>
        </div>
        <div class="p-4 space-y-4" id="comments-container">
            @forelse($ticket->comments as $comment)
                <div class="comment pl-4 py-3 {{ !$comment->is_visible ? 'comment-hidden' : '' }} {{ $comment->username === 'System' ? 'comment-system border-l-gray-400 bg-gray-50 rounded' : '' }}"
                     id="comment-{{ $comment->id }}"
                     data-visible="{{ $comment->is_visible ? 'true' : 'false' }}">

                    {{-- Comment Header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-semibold text-gray-900">{{ $comment->username }}</span>
                            <span class="text-gray-500 ml-2">
                                {{ $comment->created_at->format('d.m.Y H:i') }}
                            </span>
                            @if($comment->is_edited)
                                <span class="text-gray-400 ml-1">(bearbeitet{{ $comment->updated_at ? ' am ' . $comment->updated_at->format('d.m.Y H:i') : '' }})</span>
                            @endif
                            @if(!$comment->is_visible)
                                <span class="text-red-500 ml-1">
                                    (Ausgeblendet{{ $comment->hidden_by ? ' von ' . $comment->hidden_by : '' }}{{ $comment->hidden_at ? ' am ' . $comment->hidden_at->format('d.m.Y H:i') : '' }})
                                </span>
                            @endif
                        </div>

                        {{-- Comment Actions --}}
                        <div class="flex items-center gap-2">
                            {{-- Vote Buttons --}}
                            @if($comment->username !== 'System')
                                <div class="inline-flex rounded overflow-hidden border border-gray-200 text-xs">
                                    <button type="button"
                                            class="px-2 py-1 transition bg-white text-green-600 hover:bg-green-50"
                                            onclick="voteComment({{ $comment->id }}, 'up')"
                                            title="{{ $comment->upvoters ?: 'Keine Upvotes' }}">
                                        <i class="bi bi-hand-thumbs-up"></i> <span id="comment-up-{{ $comment->id }}">{{ $comment->up_votes }}</span>
                                    </button>
                                    <button type="button"
                                            class="px-2 py-1 border-l border-gray-200 transition bg-white text-red-600 hover:bg-red-50"
                                            onclick="voteComment({{ $comment->id }}, 'down')"
                                            title="{{ $comment->downvoters ?: 'Keine Downvotes' }}">
                                        <i class="bi bi-hand-thumbs-down"></i> <span id="comment-down-{{ $comment->id }}">{{ $comment->down_votes }}</span>
                                    </button>
                                </div>
                            @endif

                            {{-- Edit Button (own comments only) --}}
                            @if($comment->username === $username && $comment->username !== 'System')
                                <button type="button" onclick="startEditComment({{ $comment->id }})"
                                        class="text-gray-400 hover:text-brand-500 text-xs p-1" title="Bearbeiten">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endif

                            {{-- Visibility Toggle --}}
                            @if($comment->username !== 'System')
                                <button type="button"
                                        onclick="toggleCommentVisibility({{ $comment->id }})"
                                        class="text-gray-400 hover:text-brand-500 text-xs p-1"
                                        title="{{ $comment->is_visible ? 'Ausblenden' : 'Einblenden' }}">
                                    <i class="bi {{ $comment->is_visible ? 'bi-eye' : 'bi-eye-slash' }}"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Comment Content --}}
                    <div class="mt-2 text-gray-700 text-sm comment-content" id="comment-text-{{ $comment->id }}" data-raw-content="{{ e($comment->content) }}">
                        {!! $formatter->format($comment->content) !!}
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 py-6">
                    <i class="bi bi-chat-square-text text-2xl"></i>
                    <p class="mt-2">Noch keine Kommentare vorhanden</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- New Comment Form --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <h5 class="font-semibold text-gray-900 mb-3">Neuer Kommentar</h5>
        <div>
            <textarea id="commentContent" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition"
                      placeholder="Kommentar eingeben..."></textarea>
            <p class="text-xs text-gray-500 mt-1">
                Formatierung: **fett**, *kursiv*, [ ] Checkbox leer, [x] Checkbox voll. URLs werden automatisch verlinkt.
            </p>
            <div class="text-right mt-2">
                <button type="button" onclick="addComment()"
                        class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-600 transition">
                    <i class="bi bi-send"></i> Kommentar speichern
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============= Modals ============= --}}

{{-- Assignee Modal --}}
<div id="assignee-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h5 class="font-semibold text-gray-900">Zuständigkeit bearbeiten</h5>
            <button type="button" onclick="document.getElementById('assignee-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="p-4">
            <label for="assigneeInput" class="block text-sm font-medium text-gray-700 mb-1">Zuständig</label>
            <input type="text" id="assigneeInput"
                   value="{{ $ticket->assignee ?? '' }}"
                   placeholder="Name oder Gruppe eingeben"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
            <p class="text-xs text-gray-500 mt-1">Mehrere Zuständige können durch Komma getrennt werden.</p>
        </div>
        <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button type="button" onclick="document.getElementById('assignee-modal').classList.add('hidden')"
                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Abbrechen</button>
            <button type="button" onclick="updateAssignee()"
                    class="px-4 py-2 text-sm text-white bg-brand-500 rounded-lg hover:bg-brand-600 transition">Speichern</button>
        </div>
    </div>
</div>

{{-- Status Modal --}}
<div id="status-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h5 class="font-semibold text-gray-900">Status ändern</h5>
            <button type="button" onclick="document.getElementById('status-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="p-4">
            <label for="statusSelect" class="block text-sm font-medium text-gray-700 mb-1">Neuer Status</label>
            <select id="statusSelect"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                @foreach(\App\Models\TicketStatus::active()->orderBy('sort_order')->get() as $status)
                    <option value="{{ $status->id }}" {{ $status->id == $ticket->status_id ? 'selected' : '' }}>
                        {{ $status->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button type="button" onclick="document.getElementById('status-modal').classList.add('hidden')"
                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Abbrechen</button>
            <button type="button" onclick="updateStatus()"
                    class="px-4 py-2 text-sm text-white bg-brand-500 rounded-lg hover:bg-brand-600 transition">Speichern</button>
        </div>
    </div>
</div>

{{-- Follow-up Date Modal --}}
<div id="followup-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h5 class="font-semibold text-gray-900">Wiedervorlagedatum bearbeiten</h5>
            <button type="button" onclick="document.getElementById('followup-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="p-4">
            <label for="followUpDate" class="block text-sm font-medium text-gray-700 mb-1">Wiedervorlagedatum</label>
            <input type="date" id="followUpDate"
                   value="{{ $ticket->follow_up_date ? $ticket->follow_up_date->format('Y-m-d') : '' }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
        </div>
        <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button type="button" onclick="document.getElementById('followup-modal').classList.add('hidden')"
                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Abbrechen</button>
            <button type="button" onclick="updateFollowUpDate()"
                    class="px-4 py-2 text-sm text-white bg-brand-500 rounded-lg hover:bg-brand-600 transition">Speichern</button>
        </div>
    </div>
</div>

{{-- Add Contact Person Modal --}}
<div id="add-contact-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h5 class="font-semibold text-gray-900">Ansprechpartner hinzufügen</h5>
            <button type="button" onclick="document.getElementById('add-contact-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="p-4">
            @php
                $allContactPersons = \App\Models\ContactPerson::active()->orderBy('name')->get();
                $linkedIds = $ticket->contactPersons->pluck('id')->toArray();
            @endphp
            @if($allContactPersons->isEmpty())
                <div class="bg-blue-50 text-blue-700 rounded-lg px-4 py-3 text-sm">
                    <i class="bi bi-info-circle mr-1"></i> Es sind keine aktiven Ansprechpartner vorhanden.
                    <a href="{{ route('contact-persons.index') }}" class="underline font-medium">Neuen anlegen</a>
                </div>
            @else
                <label for="contactPersonSelect" class="block text-sm font-medium text-gray-700 mb-1">Ansprechpartner auswählen</label>
                <select id="contactPersonSelect"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                    <option value="">-- Bitte wählen --</option>
                    @foreach($allContactPersons as $person)
                        @if(!in_array($person->id, $linkedIds))
                            <option value="{{ $person->id }}">{{ $person->name }}{{ $person->email ? ' (' . $person->email . ')' : '' }}</option>
                        @endif
                    @endforeach
                </select>
            @endif
        </div>
        <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button type="button" onclick="document.getElementById('add-contact-modal').classList.add('hidden')"
                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Abbrechen</button>
            <button type="button" onclick="addContactPerson()"
                    class="px-4 py-2 text-sm text-white bg-brand-500 rounded-lg hover:bg-brand-600 transition">Hinzufügen</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const TICKET_ID = {{ $ticket->id }};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

function apiHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
        'Accept': 'application/json'
    };
}

function showAlert(type, message) {
    const colors = { success: 'green', danger: 'red', warning: 'yellow' };
    const color = colors[type] || 'blue';
    const alertDiv = document.createElement('div');
    alertDiv.className = 'bg-' + color + '-50 border border-' + color + '-300 text-' + color + '-800 px-4 py-3 rounded mb-4 flex items-center justify-between';
    alertDiv.innerHTML = '<span>' + message + '</span><button onclick="this.parentElement.remove()" class="ml-4"><i class="bi bi-x-lg"></i></button>';
    const main = document.querySelector('main .max-w-7xl');
    main.insertBefore(alertDiv, main.firstChild);
    setTimeout(function() { alertDiv.remove(); }, 5000);
}

// Ticket Voting
async function voteTicket(value) {
    try {
        const response = await fetch('/api/tickets/' + TICKET_ID + '/vote', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({ value: value })
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showAlert('danger', 'Fehler beim Abstimmen: ' + (data.message || ''));
        }
    } catch (error) {
        showAlert('danger', 'Fehler beim Abstimmen: ' + error.message);
    }
}

// Comment Voting
async function voteComment(commentId, value) {
    try {
        const response = await fetch('/api/comments/' + commentId + '/vote', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({ value: value })
        });
        const data = await response.json();
        if (data.success) {
            if (data.data) {
                var upEl = document.getElementById('comment-up-' + commentId);
                var downEl = document.getElementById('comment-down-' + commentId);
                if (upEl && data.data.up_votes !== undefined) upEl.textContent = data.data.up_votes;
                if (downEl && data.data.down_votes !== undefined) downEl.textContent = data.data.down_votes;
            } else {
                location.reload();
            }
        } else {
            showAlert('danger', 'Fehler beim Abstimmen: ' + (data.message || ''));
        }
    } catch (error) {
        showAlert('danger', 'Fehler beim Abstimmen: ' + error.message);
    }
}

// Add Comment
async function addComment() {
    const content = document.getElementById('commentContent').value.trim();
    if (!content) { showAlert('warning', 'Bitte geben Sie einen Kommentar ein.'); return; }

    try {
        const response = await fetch('/api/tickets/' + TICKET_ID + '/comments', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({ content: content })
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showAlert('danger', 'Fehler: ' + (data.message || 'Kommentar konnte nicht gespeichert werden'));
        }
    } catch (error) {
        showAlert('danger', 'Fehler beim Speichern: ' + error.message);
    }
}

// Edit Comment
function startEditComment(commentId) {
    const commentDiv = document.getElementById('comment-text-' + commentId);
    const content = commentDiv.getAttribute('data-raw-content') || commentDiv.textContent.trim();

    commentDiv.innerHTML =
        '<div class="mb-2">' +
            '<textarea class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none" id="edit-comment-' + commentId + '" rows="3">' + content + '</textarea>' +
        '</div>' +
        '<div class="text-xs text-gray-500 mb-2">**fett**, *kursiv*, [ ] Checkbox leer, [x] Checkbox voll</div>' +
        '<div class="flex gap-2">' +
            '<button class="bg-brand-500 text-white px-3 py-1 rounded text-xs font-medium hover:bg-brand-600" onclick="saveCommentEdit(' + commentId + ')">Speichern</button>' +
            '<button class="bg-white text-gray-700 border border-gray-300 px-3 py-1 rounded text-xs hover:bg-gray-50" onclick="location.reload()">Abbrechen</button>' +
        '</div>';
}

async function saveCommentEdit(commentId) {
    const content = document.getElementById('edit-comment-' + commentId).value;
    try {
        const response = await fetch('/api/comments/' + commentId, {
            method: 'PUT',
            headers: apiHeaders(),
            body: JSON.stringify({ content: content })
        });
        const data = await response.json();
        if (data.success) {
            showAlert('success', 'Kommentar wurde aktualisiert');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            showAlert('danger', 'Fehler: ' + (data.message || ''));
        }
    } catch (error) {
        showAlert('danger', 'Fehler beim Speichern: ' + error.message);
    }
}

// Toggle Comment Visibility
async function toggleCommentVisibility(commentId) {
    try {
        var commentEl = document.getElementById('comment-' + commentId);
        var isCurrentlyVisible = commentEl.getAttribute('data-visible') === 'true';

        const response = await fetch('/api/comments/' + commentId + '/visibility', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({ is_visible: !isCurrentlyVisible })
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showAlert('danger', 'Fehler: ' + (data.message || ''));
        }
    } catch (error) {
        showAlert('danger', 'Fehler: ' + error.message);
    }
}

// Update Status
async function updateStatus() {
    const statusId = document.getElementById('statusSelect').value;
    try {
        const response = await fetch('/api/tickets/' + TICKET_ID + '/status', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({ status_id: statusId })
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showAlert('danger', 'Fehler beim Aktualisieren des Status');
        }
    } catch (error) {
        showAlert('danger', 'Fehler: ' + error.message);
    }
}

// Update Assignee
async function updateAssignee() {
    const assignee = document.getElementById('assigneeInput').value.trim();
    try {
        const response = await fetch('/api/tickets/' + TICKET_ID + '/assignee', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({ assignee: assignee })
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showAlert('danger', 'Fehler beim Aktualisieren');
        }
    } catch (error) {
        showAlert('danger', 'Fehler: ' + error.message);
    }
}

// Update Follow-up Date
async function updateFollowUpDate() {
    const followUpDate = document.getElementById('followUpDate').value;
    try {
        const response = await fetch('/api/tickets/' + TICKET_ID + '/follow-up', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({ follow_up_date: followUpDate })
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showAlert('danger', 'Fehler: ' + (data.message || ''));
        }
    } catch (error) {
        showAlert('danger', 'Fehler: ' + error.message);
    }
}

// File Upload
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    var fileInput = document.getElementById('fileInput');
    var errorDiv = document.getElementById('uploadError');

    if (!fileInput.files.length) {
        errorDiv.textContent = 'Bitte wählen Sie eine Datei aus.';
        errorDiv.classList.remove('hidden');
        return;
    }

    var formData = new FormData();
    formData.append('file', fileInput.files[0]);

    try {
        var response = await fetch('/api/tickets/' + TICKET_ID + '/attachments', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: formData
        });
        var data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            errorDiv.textContent = data.message || 'Fehler beim Hochladen';
            errorDiv.classList.remove('hidden');
        }
    } catch (error) {
        errorDiv.textContent = 'Fehler beim Hochladen: ' + error.message;
        errorDiv.classList.remove('hidden');
    }
});

// Delete Attachment
async function deleteAttachment(attachmentId) {
    if (!confirm('Anhang wirklich löschen?')) return;
    try {
        var response = await fetch('/api/attachments/' + attachmentId, {
            method: 'DELETE',
            headers: apiHeaders()
        });
        var data = await response.json();
        if (data.success) {
            var el = document.getElementById('attachment-' + attachmentId);
            if (el) el.remove();
            showAlert('success', 'Anhang gelöscht');
        } else {
            showAlert('danger', 'Fehler: ' + (data.message || ''));
        }
    } catch (error) {
        showAlert('danger', 'Fehler: ' + error.message);
    }
}

// Add Contact Person
async function addContactPerson() {
    var select = document.getElementById('contactPersonSelect');
    if (!select || !select.value) { showAlert('warning', 'Bitte wählen Sie einen Ansprechpartner aus.'); return; }
    try {
        var response = await fetch('/api/tickets/' + TICKET_ID + '/contact-persons', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify({ contact_person_id: select.value })
        });
        var data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showAlert('danger', 'Fehler: ' + (data.message || ''));
        }
    } catch (error) {
        showAlert('danger', 'Fehler: ' + error.message);
    }
}

// Remove Contact Person
async function removeContactPerson(contactId, contactName) {
    if (!confirm('Ansprechpartner "' + contactName + '" wirklich entfernen?')) return;
    try {
        var response = await fetch('/api/tickets/' + TICKET_ID + '/contact-persons/' + contactId, {
            method: 'DELETE',
            headers: apiHeaders()
        });
        var data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showAlert('danger', 'Fehler: ' + (data.message || ''));
        }
    } catch (error) {
        showAlert('danger', 'Fehler: ' + error.message);
    }
}

// Show/hide all comments toggle
document.getElementById('showAllComments').addEventListener('change', function() {
    var comments = document.querySelectorAll('.comment-hidden');
    var self = this;
    comments.forEach(function(c) {
        c.style.display = self.checked ? '' : 'none';
    });
});

// Close modals on backdrop click
document.querySelectorAll('[id$="-modal"]').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});

// Close modals on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id$="-modal"]').forEach(function(m) { m.classList.add('hidden'); });
    }
});
</script>
@endsection
