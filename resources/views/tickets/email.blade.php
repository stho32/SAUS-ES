<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $ticket->ticket_number }} - E-Mail Ansicht</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            body { font-size: 12pt; color: #000; background: #fff; }
            .no-print { display: none !important; }
            .print-break { page-break-before: always; }
            a { color: #000; text-decoration: underline; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    {{-- Top bar (not printed) --}}
    <div class="no-print bg-white shadow-sm border-b border-gray-200 py-3">
        <div class="max-w-4xl mx-auto px-4 flex items-center justify-between">
            <a href="{{ route('tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                <i class="bi bi-arrow-left"></i> Zurueck zum Ticket
            </a>
            <button onclick="window.print()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                <i class="bi bi-printer"></i> Drucken
            </button>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            {{-- Email Subject --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Betreff</label>
                <input type="text" readonly
                       value="{{ $ticket->title }} [Unser Vorgang {{ $ticket->ticket_number }}]"
                       class="w-full border border-gray-200 rounded-lg px-4 py-2.5 bg-gray-50 text-gray-700 cursor-text select-all"
                       onclick="this.select()">
            </div>

            {{-- Email Body --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Inhalt</label>
                <div class="border border-gray-200 rounded-lg bg-gray-50 p-6">
                    {{-- Ticket Metadata --}}
                    <div class="mb-6 text-sm text-gray-700">
                        <p><strong>Status:</strong> {{ $ticket->status ? $ticket->status->name : '-' }}</p>
                        @if($ticket->affected_neighbors !== null)
                            <p><strong>Betroffene Nachbarn:</strong> {{ $ticket->affected_neighbors }}</p>
                        @endif
                        @if($ticket->assignee)
                            <p><strong>Zustaendig:</strong> {{ $ticket->assignee }}</p>
                        @endif
                        <p><strong>Erstellt am:</strong> {{ $ticket->created_at->format('d.m.Y H:i') }}</p>

                        {{-- Contact Persons --}}
                        @if($ticket->contactPersons->isNotEmpty())
                            <p class="mt-2"><strong>Ansprechpartner:</strong></p>
                            <ul class="list-disc list-inside ml-2">
                                @foreach($ticket->contactPersons as $cp)
                                    <li>
                                        {{ $cp->name }}
                                        @if($cp->email) &ndash; {{ $cp->email }} @endif
                                        @if($cp->phone) &ndash; {{ $cp->phone }} @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Description --}}
                    <div class="mb-6">
                        <div class="whitespace-pre-wrap text-gray-800">{{ $ticket->description }}</div>
                    </div>

                    {{-- Image Gallery Link --}}
                    @if($ticket->attachments->where('file_type', 'LIKE', 'image/%')->count() > 0 && $ticket->secret_string)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <p class="text-blue-800 text-sm font-medium">
                                <i class="bi bi-images"></i> Bilder anzeigen:
                            </p>
                            <a href="{{ route('public.imageview', $ticket->secret_string) }}"
                               target="_blank"
                               class="text-blue-600 hover:underline text-sm break-all">
                                Hier klicken, um die Bilder zu diesem Vorgang zu oeffnen
                            </a>
                            <p class="text-blue-600 text-xs mt-1">Dieser Link kann kopiert und per E-Mail weitergegeben werden.</p>
                        </div>
                    @endif

                    {{-- Comments / History --}}
                    @if($ticket->comments->isNotEmpty())
                        <hr class="my-6 border-gray-300">
                        <div class="mb-3">
                            <strong class="text-gray-900">Verlauf:</strong>
                        </div>
                        <div class="space-y-4">
                            @foreach($ticket->comments as $comment)
                                <div class="text-sm">
                                    <p class="text-gray-900">
                                        <strong>{{ $comment->username }}</strong>
                                        <span class="text-gray-500">({{ $comment->created_at->format('d.m.Y H:i') }}):</span>
                                    </p>
                                    <div class="whitespace-pre-wrap text-gray-700 mt-1">{{ $comment->content }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
