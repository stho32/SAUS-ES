<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Bilder zu Ticket #{{ $ticket->ticket_number ?? $ticket->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        50:  "#e6f4fb",
                        100: "#cce9f7",
                        200: "#99d3ef",
                        300: "#66bde7",
                        400: "#33a7df",
                        500: "#0786c0",
                        600: "#0675a9",
                        700: "#056492",
                        800: "#04537b",
                        900: "#034264",
                    }
                }
            }
        }
    }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        {{-- Header --}}
        <div class="mb-8 pb-4 border-b border-gray-300">
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="bi bi-images mr-2 text-brand-500"></i>
                Bilder zu Ticket #{{ $ticket->ticket_number ?? $ticket->id }}
            </h1>
            <p class="text-gray-600 mt-1">{{ $ticket->title }}</p>
            <p class="text-sm text-gray-500 mt-2">Bilder anklicken fuer Vollansicht</p>
        </div>

        @if($attachments->isEmpty())
            <div class="bg-white rounded-lg shadow text-center py-16">
                <i class="bi bi-images text-5xl text-gray-300"></i>
                <h3 class="text-gray-600 mt-3 font-medium">Keine Bilder vorhanden</h3>
                <p class="text-gray-400 text-sm mt-1">Fuer dieses Ticket wurden bisher keine Bilder hochgeladen.</p>
            </div>
        @else
            {{-- Image Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($attachments as $attachment)
                    <div class="group relative bg-white rounded-lg shadow overflow-hidden cursor-pointer hover:shadow-lg hover:-translate-y-1 transition-all duration-200"
                         onclick="openLightbox('{{ route('api.attachments.show', $attachment) }}', '{{ addslashes($attachment->original_filename) }}', '{{ number_format($attachment->file_size / 1024, 1) }}')">
                        <div class="aspect-square">
                            <img src="{{ route('api.attachments.show', $attachment) }}"
                                 alt="{{ $attachment->original_filename }}"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white px-3 py-2 text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                            {{ Str::limit($attachment->original_filename, 25) }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center text-gray-500 text-sm mt-6">
                {{ $attachments->count() }} Bild{{ $attachments->count() !== 1 ? 'er' : '' }}
            </div>
        @endif
    </div>

    {{-- Lightbox --}}
    <div id="lightbox" class="hidden fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4" onclick="closeLightbox(event)">
        <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white/70 hover:text-white transition z-10">
            <i class="bi bi-x-lg text-3xl"></i>
        </button>

        <div class="max-w-full max-h-full flex flex-col items-center">
            <img id="lightbox-img" src="" alt="Vollansicht"
                 class="max-w-full max-h-[85vh] object-contain rounded shadow-2xl">
            <div class="mt-4 text-center">
                <p id="lightbox-info" class="text-white/70 text-sm"></p>
                <a id="lightbox-download" href="" download
                   class="inline-flex items-center mt-2 bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition text-sm">
                    <i class="bi bi-download mr-2"></i> Herunterladen
                </a>
            </div>
        </div>
    </div>

    <script>
    function openLightbox(src, filename, filesize) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox-info').textContent = filename + ' (' + filesize + ' KB)';
        document.getElementById('lightbox-download').href = src;
        document.getElementById('lightbox').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox(event) {
        if (event && event.target !== event.currentTarget) return;
        document.getElementById('lightbox').classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
    </script>
</body>
</html>
