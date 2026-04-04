<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContactPerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactPersonController extends Controller
{
    public function index(): View
    {
        $contactPersons = ContactPerson::orderBy('name')->get();

        return view('contact-persons.index', compact('contactPersons'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'contact_notes' => ['nullable', 'string'],
            'responsibility_notes' => ['nullable', 'string'],
        ]);

        $contactPerson = ContactPerson::create($validated);

        return response()->json([
            'success' => true,
            'data' => $contactPerson->toArray(),
        ]);
    }

    public function update(Request $request, ContactPerson $contactPerson): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contact_notes' => ['sometimes', 'nullable', 'string'],
            'responsibility_notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $contactPerson->update($validated);

        return response()->json([
            'success' => true,
            'data' => $contactPerson->fresh()->toArray(),
        ]);
    }

    public function toggle(ContactPerson $contactPerson): JsonResponse
    {
        $contactPerson->update([
            'is_active' => !$contactPerson->is_active,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $contactPerson->id,
                'is_active' => $contactPerson->is_active,
            ],
        ]);
    }
}
