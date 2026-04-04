<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketApiController extends Controller
{
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'status_id' => ['sometimes', 'exists:ticket_status,id'],
            'assignee' => ['sometimes', 'nullable', 'string', 'max:255'],
            'showOnWebsite' => ['sometimes', 'boolean'],
            'publicComment' => ['sometimes', 'nullable', 'string'],
            'affectedNeighbors' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'followUpDate' => ['sometimes', 'nullable', 'date'],
            'doNotTrack' => ['sometimes', 'boolean'],
        ]);

        $oldFollowUpDate = $ticket->follow_up_date?->toDateString();
        $oldStatusId = $ticket->status_id;

        $updateData = [];

        if (array_key_exists('title', $validated)) {
            $updateData['title'] = $validated['title'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if (array_key_exists('status_id', $validated)) {
            $updateData['status_id'] = $validated['status_id'];
        }
        if (array_key_exists('assignee', $validated)) {
            $updateData['assignee'] = $validated['assignee'];
        }
        if (array_key_exists('showOnWebsite', $validated)) {
            $updateData['show_on_website'] = $validated['showOnWebsite'];
        }
        if (array_key_exists('publicComment', $validated)) {
            $updateData['public_comment'] = $validated['publicComment'];
        }
        if (array_key_exists('affectedNeighbors', $validated)) {
            $updateData['affected_neighbors'] = $validated['affectedNeighbors'];
        }
        if (array_key_exists('followUpDate', $validated)) {
            $updateData['follow_up_date'] = $validated['followUpDate'];
        }
        if (array_key_exists('doNotTrack', $validated)) {
            $updateData['do_not_track'] = $validated['doNotTrack'];
        }

        $ticket->update($updateData);

        // System comment for status change
        if (array_key_exists('status_id', $validated) && $validated['status_id'] != $oldStatusId) {
            $oldStatus = TicketStatus::find($oldStatusId);
            $newStatus = TicketStatus::find($validated['status_id']);

            Comment::create([
                'ticket_id' => $ticket->id,
                'username' => 'System',
                'content' => "Status geändert: {$oldStatus->name} → {$newStatus->name}",
            ]);

            // Set closed_at if the new status is closed
            if ($newStatus->is_closed && !$oldStatus->is_closed) {
                $ticket->update(['closed_at' => now()]);
            } elseif (!$newStatus->is_closed && $oldStatus->is_closed) {
                $ticket->update(['closed_at' => null]);
            }
        }

        // System comment for follow-up date change
        $newFollowUpDate = $validated['followUpDate'] ?? null;
        if (array_key_exists('followUpDate', $validated) && $newFollowUpDate !== $oldFollowUpDate) {
            if ($newFollowUpDate) {
                $message = "Wiedervorlage gesetzt auf: {$newFollowUpDate}";
            } else {
                $message = 'Wiedervorlage entfernt';
            }

            Comment::create([
                'ticket_id' => $ticket->id,
                'username' => 'System',
                'content' => $message,
            ]);
        }

        return response()->json(['success' => true, 'data' => $ticket->fresh()->load('status')]);
    }

    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->status && $ticket->status->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Archivierte Tickets können nicht mehr geändert werden.',
            ], 403);
        }

        $validated = $request->validate([
            'status_id' => ['required', 'exists:ticket_status,id'],
        ]);

        $oldStatus = $ticket->status;
        $newStatus = TicketStatus::findOrFail($validated['status_id']);

        $ticket->update(['status_id' => $validated['status_id']]);

        if ($newStatus->is_closed && !$oldStatus->is_closed) {
            $ticket->update(['closed_at' => now()]);
        } elseif (!$newStatus->is_closed && $oldStatus->is_closed) {
            $ticket->update(['closed_at' => null]);
        }

        Comment::create([
            'ticket_id' => $ticket->id,
            'username' => 'System',
            'content' => "Status geändert: {$oldStatus->name} → {$newStatus->name}",
        ]);

        return response()->json(['success' => true, 'data' => $ticket->fresh()->load('status')]);
    }

    public function updateAssignee(Request $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->status && ($ticket->status->is_closed || $ticket->status->is_archived)) {
            return response()->json([
                'success' => false,
                'message' => 'Geschlossene oder archivierte Tickets können nicht mehr geändert werden.',
            ], 403);
        }

        $validated = $request->validate([
            'assignee' => ['nullable', 'string', 'max:255'],
        ]);

        $ticket->update(['assignee' => $validated['assignee']]);

        return response()->json(['success' => true, 'data' => $ticket->fresh()]);
    }

    public function updateFollowUp(Request $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->status && ($ticket->status->is_closed || $ticket->status->is_archived)) {
            return response()->json([
                'success' => false,
                'message' => 'Geschlossene oder archivierte Tickets können nicht mehr geändert werden.',
            ], 403);
        }

        $validated = $request->validate([
            'follow_up_date' => ['nullable', 'date'],
        ]);

        $oldDate = $ticket->follow_up_date?->toDateString();
        $newDate = $validated['follow_up_date'];

        $ticket->update(['follow_up_date' => $newDate]);

        if ($newDate !== $oldDate) {
            if ($newDate) {
                $message = "Wiedervorlage gesetzt auf: {$newDate}";
            } else {
                $message = 'Wiedervorlage entfernt';
            }

            Comment::create([
                'ticket_id' => $ticket->id,
                'username' => 'System',
                'content' => $message,
            ]);
        }

        return response()->json(['success' => true, 'data' => $ticket->fresh()]);
    }

    public function getVotes(Ticket $ticket): JsonResponse
    {
        $upVotes = $ticket->ticketVotes()->where('value', 'up')->count();
        $downVotes = $ticket->ticketVotes()->where('value', 'down')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'up_votes' => $upVotes,
                'down_votes' => $downVotes,
            ],
        ]);
    }
}
