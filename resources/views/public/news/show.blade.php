@extends('layouts.public')

@section('title', $news->title)

@section('content')
<a href="{{ route('public.news.index') }}" class="inline-flex items-center text-brand-500 hover:text-brand-800 text-sm mb-6 transition">
    <i class="bi bi-arrow-left mr-1"></i> Zurueck zur Uebersicht
</a>

<article class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 sm:p-8">
        {{-- Event Date --}}
        <p class="text-sm text-gray-500 mb-2">
            <i class="bi bi-calendar-event mr-1"></i>
            {{ $news->event_date?->format('d.m.Y') ?? '-' }}
        </p>

        {{-- Title --}}
        <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ $news->title }}</h1>

        {{-- Image --}}
        @if($news->image_filename)
            <div class="mb-6 text-center">
                <img src="{{ route('public.news.image', $news) }}"
                     alt="{{ $news->title }}"
                     class="max-w-full h-auto rounded-lg shadow-md inline-block cursor-pointer"
                     onclick="toggleFullSize(this)"
                     style="max-height: 500px;">
            </div>
        @endif

        {{-- Content --}}
        <div class="prose max-w-none text-gray-700 leading-relaxed">
            {!! $news->content !!}
        </div>

        {{-- Meta --}}
        <div class="mt-8 pt-4 border-t border-gray-200 text-sm text-gray-500">
            <i class="bi bi-clock mr-1"></i>
            Veroeffentlicht: {{ $news->created_at?->format('d.m.Y') }}
        </div>
    </div>
</article>
@endsection

@section('scripts')
<script>
function toggleFullSize(img) {
    if (img.style.maxHeight === 'none') {
        img.style.maxHeight = '500px';
        img.style.cursor = 'pointer';
    } else {
        img.style.maxHeight = 'none';
        img.style.cursor = 'zoom-out';
    }
}
</script>
@endsection
