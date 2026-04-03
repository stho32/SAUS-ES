@extends('layouts.app')

@section('title', 'Ansprechpartner')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Ansprechpartner</h1>

{{-- Success/Error Messages --}}
<div id="flashMessage" class="hidden mb-4"></div>

{{-- Add Form --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Neuen Ansprechpartner hinzufuegen</h2>
    <form id="addContactForm" onsubmit="return createContactPerson(event)" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                <input type="email" id="email" name="email"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                <input type="text" id="phone" name="phone"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="is_active" name="is_active" checked
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Aktiv</span>
                </label>
            </div>
        </div>
        <div>
            <label for="contact_notes" class="block text-sm font-medium text-gray-700 mb-1">Notizen</label>
            <textarea id="contact_notes" name="contact_notes" rows="2"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                      placeholder="Weitere Kontaktinformationen..."></textarea>
        </div>
        <div>
            <label for="responsibility_notes" class="block text-sm font-medium text-gray-700 mb-1">Zustaendigkeit</label>
            <textarea id="responsibility_notes" name="responsibility_notes" rows="2"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                      placeholder="Zustaendigkeitsbereiche..."></textarea>
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition text-sm">
            <i class="bi bi-plus-lg mr-1"></i> Hinzufuegen
        </button>
    </form>
</div>

{{-- Contact Person List --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Ansprechpartner</h2>
    </div>

    @if($contactPersons->isEmpty())
        <div class="text-center py-12">
            <i class="bi bi-people text-5xl text-gray-300"></i>
            <p class="text-gray-500 mt-3">Keine Ansprechpartner vorhanden.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Name</th>
                        <th class="px-4 py-3 text-left font-semibold">E-Mail</th>
                        <th class="px-4 py-3 text-left font-semibold">Telefon</th>
                        <th class="px-4 py-3 text-left font-semibold">Notizen</th>
                        <th class="px-4 py-3 text-left font-semibold">Zustaendigkeit</th>
                        <th class="px-4 py-3 text-center font-semibold w-20">Status</th>
                        <th class="px-4 py-3 text-center font-semibold w-40">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="contactPersonsBody">
                    @foreach($contactPersons as $person)
                        <tr id="person-row-{{ $person->id }}" class="{{ !$person->is_active ? 'bg-gray-100 opacity-60' : '' }}">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $person->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $person->email ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $person->phone ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ Str::limit($person->contact_notes, 60) ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ Str::limit($person->responsibility_notes, 60) ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($person->is_active)
                                    <span class="inline-block px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs font-medium">Aktiv</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 bg-gray-200 text-gray-600 rounded text-xs font-medium">Inaktiv</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="inline-flex gap-1">
                                    <button type="button"
                                            onclick="openEditModal({{ json_encode($person) }})"
                                            class="inline-flex items-center justify-center w-8 h-8 border border-indigo-300 text-indigo-600 rounded hover:bg-indigo-50 transition"
                                            title="Bearbeiten">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button"
                                            onclick="toggleStatus({{ $person->id }}, {{ $person->is_active ? 'true' : 'false' }})"
                                            class="inline-flex items-center justify-center w-8 h-8 border {{ $person->is_active ? 'border-amber-300 text-amber-600 hover:bg-amber-50' : 'border-green-300 text-green-600 hover:bg-green-50' }} rounded transition"
                                            title="{{ $person->is_active ? 'Deaktivieren' : 'Aktivieren' }}">
                                        <i class="bi {{ $person->is_active ? 'bi-x-circle' : 'bi-check-circle' }}"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Edit Modal --}}
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Ansprechpartner bearbeiten</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="bi bi-x-lg text-xl"></i>
            </button>
        </div>
        <form id="editContactForm" onsubmit="return updateContactPerson(event)" class="p-4 space-y-4">
            <input type="hidden" id="edit-id">
            <div>
                <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-1">
                    Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="edit-name" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label for="edit-email" class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                <input type="email" id="edit-email"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label for="edit-phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                <input type="text" id="edit-phone"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label for="edit-contact_notes" class="block text-sm font-medium text-gray-700 mb-1">Notizen</label>
                <textarea id="edit-contact_notes" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"></textarea>
            </div>
            <div>
                <label for="edit-responsibility_notes" class="block text-sm font-medium text-gray-700 mb-1">Zustaendigkeit</label>
                <textarea id="edit-responsibility_notes" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"></textarea>
            </div>
            <div class="flex items-center justify-between pt-2">
                <button type="button" onclick="closeEditModal()" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-sm">
                    Abbrechen
                </button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition text-sm">
                    Speichern
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function showFlash(message, type = 'success') {
    const flash = document.getElementById('flashMessage');
    const bgClass = type === 'success' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800';
    flash.className = `border px-4 py-3 rounded mb-4 ${bgClass}`;
    flash.textContent = message;
    flash.classList.remove('hidden');
    setTimeout(() => flash.classList.add('hidden'), 4000);
}

async function createContactPerson(event) {
    event.preventDefault();

    const data = {
        name: document.getElementById('name').value.trim(),
        email: document.getElementById('email').value.trim() || null,
        phone: document.getElementById('phone').value.trim() || null,
        contact_notes: document.getElementById('contact_notes').value.trim() || null,
        responsibility_notes: document.getElementById('responsibility_notes').value.trim() || null,
    };

    if (!data.name) {
        alert('Name ist ein Pflichtfeld.');
        return false;
    }

    try {
        const response = await fetch('{{ route("contact-persons.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();
        if (result.success) {
            showFlash('Ansprechpartner erfolgreich erstellt.');
            window.location.reload();
        } else {
            showFlash('Fehler beim Erstellen.', 'error');
        }
    } catch (error) {
        showFlash('Fehler: ' + error.message, 'error');
    }

    return false;
}

function openEditModal(person) {
    document.getElementById('edit-id').value = person.id;
    document.getElementById('edit-name').value = person.name || '';
    document.getElementById('edit-email').value = person.email || '';
    document.getElementById('edit-phone').value = person.phone || '';
    document.getElementById('edit-contact_notes').value = person.contact_notes || '';
    document.getElementById('edit-responsibility_notes').value = person.responsibility_notes || '';
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

async function updateContactPerson(event) {
    event.preventDefault();
    const id = document.getElementById('edit-id').value;

    const data = {
        name: document.getElementById('edit-name').value.trim(),
        email: document.getElementById('edit-email').value.trim() || null,
        phone: document.getElementById('edit-phone').value.trim() || null,
        contact_notes: document.getElementById('edit-contact_notes').value.trim() || null,
        responsibility_notes: document.getElementById('edit-responsibility_notes').value.trim() || null,
    };

    if (!data.name) {
        alert('Name ist ein Pflichtfeld.');
        return false;
    }

    try {
        const response = await fetch('/contact-persons/' + id, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();
        if (result.success) {
            showFlash('Ansprechpartner erfolgreich aktualisiert.');
            closeEditModal();
            window.location.reload();
        } else {
            showFlash('Fehler beim Aktualisieren.', 'error');
        }
    } catch (error) {
        showFlash('Fehler: ' + error.message, 'error');
    }

    return false;
}

async function toggleStatus(id, currentlyActive) {
    const action = currentlyActive ? 'deaktivieren' : 'aktivieren';
    if (!confirm(`Moechten Sie diesen Ansprechpartner wirklich ${action}?`)) return;

    try {
        const response = await fetch('/contact-persons/' + id + '/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const result = await response.json();
        if (result.success) {
            showFlash(`Ansprechpartner erfolgreich ${action === 'deaktivieren' ? 'deaktiviert' : 'aktiviert'}.`);
            window.location.reload();
        } else {
            showFlash('Fehler beim Aendern des Status.', 'error');
        }
    } catch (error) {
        showFlash('Fehler: ' + error.message, 'error');
    }
}
</script>
@endsection
