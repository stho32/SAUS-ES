@extends('layouts.app')

@section('title', 'News verwalten')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
    <h1 class="text-2xl font-bold text-gray-900">News verwalten</h1>
    <a href="{{ route('news.create') }}"
       class="inline-flex items-center bg-brand-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-brand-600 transition text-sm">
        <i class="bi bi-plus-lg mr-2"></i> Neuer Artikel
    </a>
</div>

{{-- Search and Sort --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('news.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-5">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                <i class="bi bi-search"></i> Suche
            </label>
            <input type="text"
                   id="search"
                   name="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Titel, Inhalt oder Ersteller durchsuchen..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
        </div>
        <div class="md:col-span-3">
            <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">
                <i class="bi bi-sort-down"></i> Sortieren nach
            </label>
            <select id="sort" name="sort"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                <option value="id" {{ ($sortBy ?? '') === 'id' ? 'selected' : '' }}>ID</option>
                <option value="title" {{ ($sortBy ?? '') === 'title' ? 'selected' : '' }}>Titel</option>
                <option value="event_date" {{ ($sortBy ?? '') === 'event_date' ? 'selected' : '' }}>Veranstaltungsdatum</option>
                <option value="created_at" {{ ($sortBy ?? 'created_at') === 'created_at' ? 'selected' : '' }}>Erstellt am</option>
                <option value="created_by" {{ ($sortBy ?? '') === 'created_by' ? 'selected' : '' }}>Ersteller</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="dir" class="block text-sm font-medium text-gray-700 mb-1">Reihenfolge</label>
            <select id="dir" name="dir"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                <option value="desc" {{ ($sortDir ?? 'desc') === 'desc' ? 'selected' : '' }}>Absteigend</option>
                <option value="asc" {{ ($sortDir ?? '') === 'asc' ? 'selected' : '' }}>Aufsteigend</option>
            </select>
        </div>
        <div class="md:col-span-2 flex items-end gap-2">
            <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg hover:bg-brand-600 transition text-sm">
                <i class="bi bi-funnel"></i> Filtern
            </button>
            @if(($search ?? '') !== '' || ($sortBy ?? 'created_at') !== 'created_at' || ($sortDir ?? 'desc') !== 'desc')
                <a href="{{ route('news.index') }}" class="border border-gray-300 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Results info --}}
<div class="flex justify-between items-center mb-3 text-sm text-gray-600">
    <div>
        <strong>{{ $news->total() }}</strong> News-Artikel gefunden
        @if($search)
            <span class="text-gray-400">fuer "{{ $search }}"</span>
        @endif
    </div>
    @if($news->lastPage() > 1)
        <div>Seite {{ $news->currentPage() }} von {{ $news->lastPage() }}</div>
    @endif
</div>

{{-- News Table --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    @if($news->isEmpty())
        <div class="text-center py-12">
            <i class="bi bi-inbox text-5xl text-gray-300"></i>
            <p class="text-gray-500 mt-3">
                @if($search)
                    Keine News-Artikel gefunden, die Ihrer Suche entsprechen.
                @else
                    Keine News-Artikel vorhanden.
                @endif
            </p>
            <a href="{{ route('news.create') }}" class="inline-flex items-center mt-4 bg-brand-500 text-white px-4 py-2 rounded-lg hover:bg-brand-600 transition text-sm">
                <i class="bi bi-plus-lg mr-2"></i> Erste News erstellen
            </a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold w-16">ID</th>
                        <th class="px-4 py-3 text-left font-semibold">Titel</th>
                        <th class="px-4 py-3 text-left font-semibold">Veranstaltungsdatum</th>
                        <th class="px-4 py-3 text-left font-semibold">Erstellt</th>
                        <th class="px-4 py-3 text-left font-semibold">Ersteller</th>
                        <th class="px-4 py-3 text-center font-semibold w-16">Bild</th>
                        <th class="px-4 py-3 text-center font-semibold w-32">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($news as $article)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-500">{{ $article->id }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $article->title }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                <i class="bi bi-calendar-event mr-1"></i>
                                {{ $article->event_date?->format('d.m.Y') ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ $article->created_at?->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $article->created_by }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($article->image_filename)
                                    <img src="{{ route('api.news.image', $article) }}?thumbnail=true"
                                         alt="Bild"
                                         class="w-10 h-10 rounded object-cover inline-block">
                                @else
                                    <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center inline-flex">
                                        <i class="bi bi-image text-gray-400"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="inline-flex gap-1">
                                    <a href="{{ route('news.edit', $article) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 border border-brand-300 text-brand-500 rounded hover:bg-brand-50 transition"
                                       title="Bearbeiten">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            onclick="deleteNews({{ $article->id }}, '{{ addslashes($article->title) }}')"
                                            class="inline-flex items-center justify-center w-8 h-8 border border-red-300 text-red-600 rounded hover:bg-red-50 transition"
                                            title="Loeschen">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($news->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $news->links() }}
            </div>
        @endif
    @endif
</div>
@endsection

@section('scripts')
<script>
async function deleteNews(newsId, title) {
    if (!confirm(`Moechten Sie die News "${title}" wirklich loeschen?\n\nDiese Aktion kann nicht rueckgaengig gemacht werden.`)) {
        return;
    }

    try {
        const response = await fetch(`/api/news/${newsId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const result = await response.json();

        if (result.success) {
            window.location.reload();
        } else {
            alert('Fehler beim Loeschen: ' + (result.message || 'Unbekannter Fehler'));
        }
    } catch (error) {
        alert('Fehler beim Loeschen: ' + error.message);
    }
}
</script>
@endsection
