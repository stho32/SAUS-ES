<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ContactPerson;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactPersonApiController extends Controller
{
    public function linkToTicket(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'contactPersonId' => ['required', 'exists:contact_persons,id'],
        ]);

        $contactPerson = ContactPerson::findOrFail($validated['contactPersonId']);

        // Check if already linked
        if ($ticket->contactPersons()->where('contact_person_id', $contactPerson->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ansprechpartner ist bereits mit diesem Ticket verknüpft.',
            ], 422);
        }

        $ticket->contactPersons()->attach($contactPerson->id);

        Comment::create([
            'ticket_id' => $ticket->id,
            'username' => 'System',
            'content' => "Ansprechpartner hinzugefügt: {$contactPerson->name}",
        ]);

        return response()->json([
            'success' => true,
            'data' => $contactPerson->toArray(),
        ]);
    }

    public function unlinkFromTicket(Request $request, Ticket $ticket, ContactPerson $contactPerson): JsonResponse
    {
        $ticket->contactPersons()->detach($contactPerson->id);

        Comment::create([
            'ticket_id' => $ticket->id,
            'username' => 'System',
            'content' => "Ansprechpartner entfernt: {$contactPerson->name}",
        ]);

        return response()->json(['success' => true, 'data' => null]);
    }
}
