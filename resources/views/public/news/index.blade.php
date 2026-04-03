@extends('layouts.public')

@section('title', 'Neuigkeiten')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Neuigkeiten</h1>

{{-- Search --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('public.news.index') }}" class="flex gap-3">
        <div class="flex-1">
            <label for="search" class="sr-only">Suche</label>
            <input type="text"
                   id="search"
                   name="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Suchbegriff eingeben..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none text-sm">
        </div>
        <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg hover:bg-brand-600 transition text-sm">
            <i class="bi bi-search"></i> Suchen
        </button>
        @if($search)
            <a href="{{ route('public.news.index') }}" class="border border-gray-300 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </form>
</div>

@if($search)
    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-2 rounded-lg mb-4 text-sm">
        <i class="bi bi-info-circle mr-1"></i>
        {{ $news->total() }} Ergebnis{{ $news->total() !== 1 ? 'se' : '' }} fuer "<strong>{{ $search }}</strong>"
    </div>
@endif

{{-- News List --}}
@if($news->isEmpty())
    <div class="text-center py-12">
        <i class="bi bi-inbox text-5xl text-gray-300"></i>
        <h3 class="text-gray-600 mt-3 font-medium">Keine News gefunden</h3>
        @if($search)
            <p class="text-gray-400 text-sm mt-1">Versuchen Sie es mit einem anderen Suchbegriff.</p>
        @else
            <p class="text-gray-400 text-sm mt-1">Derzeit sind keine News-Artikel verfuegbar.</p>
        @endif
    </div>
@else
    @foreach($news as $article)
        <div class="bg-white rounded-lg shadow mb-6 overflow-hidden hover:shadow-lg transition">
            <div class="p-6">
                <a href="{{ route('public.news.show', $article) }}" class="block group">
                    <div class="flex flex-col sm:flex-row gap-4">
                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-500 mb-1">
                                <i class="bi bi-calendar-event mr-1"></i>
                                {{ $article->event_date?->format('d.m.Y') ?? '-' }}
                            </p>

                            <h2 class="text-xl font-semibold text-gray-900 group-hover:text-brand-500 transition mb-2">
                                {{ $article->title }}
                            </h2>

                            <p class="text-gray-600 text-sm line-clamp-3">
                                {{ Str::limit(strip_tags($article->content), 250) }}
                            </p>

                            <p class="text-xs text-gray-400 mt-3">
                                <i class="bi bi-clock mr-1"></i>
                                Veroeffentlicht: {{ $article->created_at?->format('d.m.Y') }}
                            </p>
                        </div>

                        {{-- Image --}}
                        @if($article->image_filename)
                            <div class="sm:flex-shrink-0">
                                <img src="{{ route('public.news.image', $article) }}?thumbnail=true"
                                     alt="{{ $article->title }}"
                                     class="w-full sm:w-48 h-32 object-cover rounded-lg shadow-sm">
                            </div>
                        @endif
                    </div>
                </a>
            </div>
        </div>
    @endforeach

    {{-- Pagination --}}
    @if($news->hasPages())
        <div class="flex items-center justify-between mt-6">
            <div>
                @if($news->previousPageUrl())
                    <a href="{{ $news->previousPageUrl() }}"
                       class="inline-flex items-center border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
                        <i class="bi bi-chevron-left mr-1"></i> Zurueck
                    </a>
                @endif
            </div>

            <div class="text-sm text-gray-500">
                Seite {{ $news->currentPage() }} von {{ $news->lastPage() }}
                ({{ $news->total() }} News gesamt)
            </div>

            <div>
                @if($news->nextPageUrl())
                    <a href="{{ $news->nextPageUrl() }}"
                       class="inline-flex items-center border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
                        Vor <i class="bi bi-chevron-right ml-1"></i>
                    </a>
                @endif
            </div>
        </div>
    @endif
@endif
@endsection
