@extends('layouts.app')

@section('title', 'Neues Ticket erstellen')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Neues Ticket erstellen</h1>
    </div>
    <a href="{{ route('tickets.index') }}" class="text-gray-600 hover:text-gray-800 border border-gray-300 bg-white px-4 py-2 rounded-lg text-sm font-medium transition">
        <i class="bi bi-x-lg"></i> Abbrechen
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <form method="POST" action="{{ route('tickets.store') }}" class="space-y-5">
        @csrf

        {{-- Title --}}
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titel <span class="text-red-500">*</span></label>
            <input type="text"
                   id="title"
                   name="title"
                   value="{{ old('title') }}"
                   required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition @error('title') border-red-500 @enderror"
                   placeholder="Kurze Beschreibung des Problems">
            @error('title')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Beschreibung <span class="text-red-500">*</span></label>
            <textarea id="description"
                      name="description"
                      rows="5"
                      required
                      class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition @error('description') border-red-500 @enderror"
                      placeholder="Detaillierte Beschreibung des Problems...">{{ old('description') }}</textarea>
            @error('description')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Status --}}
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
            <select id="status"
                    name="status_id"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition @error('status_id') border-red-500 @enderror">
                <option value="">Bitte wählen...</option>
                @foreach($statuses ?? [] as $status)
                    <option value="{{ $status['id'] }}" {{ old('status_id') == $status['id'] ? 'selected' : '' }}>
                        {{ $status['name'] }}
                    </option>
                @endforeach
            </select>
            @error('status_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Assignee --}}
        <div>
            <label for="assignee" class="block text-sm font-medium text-gray-700 mb-1">Bearbeiter</label>
            <input type="text"
                   id="assignee"
                   name="assignee"
                   value="{{ old('assignee') }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition"
                   placeholder="Name des zuständigen Bearbeiters (optional)">
        </div>

        {{-- Submit --}}
        <div class="flex justify-end pt-2">
            <button type="submit"
                    class="bg-brand-500 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-brand-600 focus:ring-4 focus:ring-brand-300 transition">
                <i class="bi bi-check-lg"></i> Ticket erstellen
            </button>
        </div>
    </form>
</div>
@endsection
