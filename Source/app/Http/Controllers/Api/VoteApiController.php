<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Ticket;
use App\Models\TicketVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoteApiController extends Controller
{
    public function voteTicket(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'in:up,down,none'],
        ]);

        $username = session('username');

        if (!$username) {
            return response()->json([
                'success' => false,
                'message' => 'Benutzername nicht gesetzt.',
            ], 401);
        }

        if ($validated['value'] === 'none') {
            TicketVote::where('ticket_id', $ticket->id)
                ->where('username', $username)
                ->delete();
        } else {
            TicketVote::updateOrCreate(
                [
                    'ticket_id' => $ticket->id,
                    'username' => $username,
                ],
                [
                    'value' => $validated['value'],
                ]
            );
        }

        $upVotes = $ticket->ticketVotes()->where('value', 'up')->count();
        $downVotes = $ticket->ticketVotes()->where('value', 'down')->count();
        $upvoters = $ticket->ticketVotes()->where('value', 'up')->pluck('username')->implode(', ');
        $downvoters = $ticket->ticketVotes()->where('value', 'down')->pluck('username')->implode(', ');

        return response()->json([
            'success' => true,
            'data' => [
                'up_votes' => $upVotes,
                'down_votes' => $downVotes,
                'upvoters' => $upvoters ?: 'Keine Upvotes',
                'downvoters' => $downvoters ?: 'Keine Downvotes',
            ],
        ]);
    }

    public function voteComment(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'in:up,down,none'],
        ]);

        $username = session('username');

        if (!$username) {
            return response()->json([
                'success' => false,
                'message' => 'Benutzername nicht gesetzt.',
            ], 401);
        }

        if ($validated['value'] === 'none') {
            CommentVote::where('comment_id', $comment->id)
                ->where('username', $username)
                ->delete();
        } else {
            CommentVote::updateOrCreate(
                [
                    'comment_id' => $comment->id,
                    'username' => $username,
                ],
                [
                    'value' => $validated['value'],
                ]
            );
        }

        $upVotes = $comment->votes()->where('value', 'up')->count();
        $downVotes = $comment->votes()->where('value', 'down')->count();
        $upvoters = $comment->votes()->where('value', 'up')->pluck('username')->implode(', ');
        $downvoters = $comment->votes()->where('value', 'down')->pluck('username')->implode(', ');

        return response()->json([
            'success' => true,
            'data' => [
                'up_votes' => $upVotes,
                'down_votes' => $downVotes,
                'upvoters' => $upvoters ?: 'Keine Upvotes',
                'downvoters' => $downvoters ?: 'Keine Downvotes',
            ],
        ]);
    }
}
