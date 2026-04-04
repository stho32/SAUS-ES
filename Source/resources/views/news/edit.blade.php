@extends('layouts.app')

@section('title', isset($news) ? 'News bearbeiten' : 'News erstellen')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            {{ isset($news) ? 'News bearbeiten' : 'News erstellen' }}
        </h1>
        @if(isset($news))
            <p class="text-sm text-gray-500">ID #{{ $news->id }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('news.index') }}" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
            <i class="bi bi-x-lg mr-1"></i> Abbrechen
        </a>
        <button type="button" id="saveButton" onclick="saveNews()"
                class="bg-brand-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-brand-600 transition text-sm">
            <i class="bi bi-check-lg mr-1"></i> Speichern
        </button>
    </div>
</div>

<input type="hidden" id="newsId" value="{{ $news->id ?? '' }}">
<input type="hidden" id="imageFilename" value="{{ $news->image_filename ?? '' }}">

<form id="newsForm" novalidate>
    {{-- General Information --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Allgemeine Informationen</h2>

        <div class="mb-4">
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                Titel <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   id="title"
                   value="{{ $news->title ?? '' }}"
                   required
                   maxlength="255"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
            <p class="hidden text-red-500 text-xs mt-1" id="title-error">Bitte geben Sie einen Titel ein.</p>
        </div>

        <div class="mb-4">
            <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                Inhalt <span class="text-red-500">*</span>
            </label>
            <textarea id="content"
                      rows="10"
                      required
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">{{ $news->content ?? '' }}</textarea>
            <p class="text-gray-500 text-xs mt-1">
                Formatierung: **fett**, *kursiv*, URLs werden automatisch erkannt. HTML ist erlaubt.
            </p>
            <p class="hidden text-red-500 text-xs mt-1" id="content-error">Bitte geben Sie einen Inhalt ein.</p>
        </div>

        <div class="mb-4">
            <label for="eventDate" class="block text-sm font-medium text-gray-700 mb-1">
                Veranstaltungsdatum <span class="text-red-500">*</span>
            </label>
            <input type="date"
                   id="eventDate"
                   value="{{ isset($news) ? $news->event_date->format('Y-m-d') : '' }}"
                   required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
            <p class="text-gray-500 text-xs mt-1">Datum der Veranstaltung oder des Events</p>
            <p class="hidden text-red-500 text-xs mt-1" id="eventDate-error">Bitte geben Sie ein Veranstaltungsdatum ein.</p>
        </div>

        @if(isset($news))
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-sm text-gray-500">
                    <i class="bi bi-clock mr-1"></i>
                    Erstellt am {{ $news->created_at->format('d.m.Y H:i') }} Uhr
                    von <strong>{{ $news->created_by }}</strong>
                </p>
            </div>
        @endif
    </div>

    {{-- Image --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Bild</h2>

        @if(isset($news) && $news->image_filename)
            <div id="currentImagePreview" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Aktuelles Bild</label>
                <div class="relative inline-block max-w-md">
                    <img src="{{ route('api.news.image', $news) }}?thumbnail=true&t={{ time() }}"
                         alt="Vorschau"
                         class="rounded-lg shadow-sm cursor-pointer max-w-full"
                         onclick="showFullImage({{ $news->id }})">
                    <button type="button"
                            onclick="showFullImage({{ $news->id }})"
                            class="absolute top-2 right-2 bg-white/80 hover:bg-white text-gray-700 w-8 h-8 rounded-lg flex items-center justify-center shadow transition"
                            title="In voller Größe anzeigen">
                        <i class="bi bi-arrows-fullscreen"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">Klicken Sie auf das Bild, um es in voller Größe anzuzeigen</p>
            </div>
        @endif

        <div>
            <label for="imageUpload" class="block text-sm font-medium text-gray-700 mb-1">
                {{ (isset($news) && $news->image_filename) ? 'Neues Bild hochladen' : 'Bild hochladen' }}
            </label>
            <input type="file"
                   id="imageUpload"
                   accept="image/jpeg,image/png,image/gif"
                   onchange="uploadImage()"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-brand-50 file:text-brand-600 hover:file:bg-brand-100">
            <p class="text-gray-500 text-xs mt-1">Max. 2MB, nur JPG, PNG oder GIF</p>

            <div id="uploadProgress" class="hidden mt-2">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-brand-500 h-2 rounded-full animate-pulse w-full"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Wird hochgeladen...</p>
            </div>

            <div id="uploadSuccess" class="hidden mt-2 bg-green-50 border border-green-300 text-green-800 px-3 py-2 rounded-lg text-sm">
                <i class="bi bi-check-circle mr-1"></i> Bild erfolgreich hochgeladen
            </div>

            <div id="uploadError" class="hidden mt-2 bg-red-50 border border-red-300 text-red-800 px-3 py-2 rounded-lg text-sm"></div>
        </div>
    </div>
</form>

{{-- Full-size Image Modal --}}
<div id="imageModal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4" onclick="closeImageModal(event)">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl max-h-[90vh] overflow-auto">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="font-semibold text-gray-900">Bild in voller Größe</h3>
            <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="bi bi-x-lg text-xl"></i>
            </button>
        </div>
        <div class="p-4 text-center">
            <img id="fullSizeImage" src="" alt="Vollbild" class="max-w-full max-h-[75vh] inline-block">
        </div>
    </div>
</div>

<div class="mb-6">
    <a href="{{ route('news.index') }}" class="text-brand-500 hover:text-brand-800 text-sm">
        <i class="bi bi-arrow-left mr-1"></i> Zurück zur Übersicht
    </a>
</div>
@endsection

@section('scripts')
<script>
function showFullImage(newsId) {
    const modal = document.getElementById('imageModal');
    const img = document.getElementById('fullSizeImage');
    img.src = '/api/news/' + newsId + '/image?t=' + Date.now();
    modal.classList.remove('hidden');
}

function closeImageModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('imageModal').classList.add('hidden');
}

async function uploadImage() {
    const fileInput = document.getElementById('imageUpload');
    const file = fileInput.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        showUploadError('Datei zu gross (max 2MB)');
        fileInput.value = '';
        return;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showUploadError('Nur JPG, PNG oder GIF erlaubt');
        fileInput.value = '';
        return;
    }

    let currentNewsId = document.getElementById('newsId').value;

    // Create news first if needed
    if (!currentNewsId) {
        const title = document.getElementById('title').value.trim();
        const content = document.getElementById('content').value.trim();
        const eventDate = document.getElementById('eventDate').value;

        if (!title || !content || !eventDate) {
            showUploadError('Bitte fuellen Sie zuerst Titel, Inhalt und Datum aus');
            fileInput.value = '';
            return;
        }

        try {
            const response = await fetch('/api/news', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ title, content, event_date: eventDate, image_filename: '' }),
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message || 'Fehler beim Erstellen');
            currentNewsId = result.data.id;
            document.getElementById('newsId').value = currentNewsId;
        } catch (error) {
            showUploadError('Fehler: ' + error.message);
            fileInput.value = '';
            return;
        }
    }

    document.getElementById('uploadProgress').classList.remove('hidden');
    document.getElementById('uploadSuccess').classList.add('hidden');
    document.getElementById('uploadError').classList.add('hidden');

    const formData = new FormData();
    formData.append('file', file);
    formData.append('newsId', currentNewsId);

    try {
        const response = await fetch('/api/news/' + currentNewsId + '/image', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });
        const result = await response.json();

        if (result.success || result.filename) {
            document.getElementById('imageFilename').value = result.filename || '';
            document.getElementById('uploadProgress').classList.add('hidden');
            document.getElementById('uploadSuccess').classList.remove('hidden');
            setTimeout(() => document.getElementById('uploadSuccess').classList.add('hidden'), 3000);
        } else {
            throw new Error(result.error || result.message || 'Upload fehlgeschlagen');
        }
    } catch (error) {
        document.getElementById('uploadProgress').classList.add('hidden');
        showUploadError(error.message);
        fileInput.value = '';
    }
}

function showUploadError(message) {
    const errorDiv = document.getElementById('uploadError');
    errorDiv.textContent = message;
    errorDiv.classList.remove('hidden');
    setTimeout(() => errorDiv.classList.add('hidden'), 5000);
}

async function saveNews() {
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    const eventDate = document.getElementById('eventDate').value;

    // Validate
    let valid = true;
    document.getElementById('title-error').classList.add('hidden');
    document.getElementById('content-error').classList.add('hidden');
    document.getElementById('eventDate-error').classList.add('hidden');

    if (!title) {
        document.getElementById('title-error').classList.remove('hidden');
        valid = false;
    }
    if (!content) {
        document.getElementById('content-error').classList.remove('hidden');
        valid = false;
    }
    if (!eventDate) {
        document.getElementById('eventDate-error').classList.remove('hidden');
        valid = false;
    }
    if (!valid) return;

    const newsId = document.getElementById('newsId').value;
    const imageFilename = document.getElementById('imageFilename').value;

    const data = { title, content, event_date: eventDate, image_filename: imageFilename };

    try {
        const saveButton = document.getElementById('saveButton');
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></span> Speichern...';

        let apiUrl, method;
        if (newsId) {
            apiUrl = '/api/news/' + newsId;
            method = 'PUT';
        } else {
            apiUrl = '/api/news';
            method = 'POST';
        }

        const response = await fetch(apiUrl, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = '{{ route("news.index") }}';
        } else {
            throw new Error(result.message || 'Fehler beim Speichern');
        }
    } catch (error) {
        alert('Fehler: ' + error.message);
        const saveButton = document.getElementById('saveButton');
        saveButton.disabled = false;
        saveButton.innerHTML = '<i class="bi bi-check-lg mr-1"></i> Speichern';
    }
}
</script>
@endsection
