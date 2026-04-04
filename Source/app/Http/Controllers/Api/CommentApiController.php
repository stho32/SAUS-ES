<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Ticket;
use App\Services\CommentFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentApiController extends Controller
{
    public function __construct(
        private readonly CommentFormatter $commentFormatter,
    ) {}

    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $username = session('username');

        if (!$username) {
            return response()->json([
                'success' => false,
                'message' => 'Benutzername nicht gesetzt.',
            ], 401);
        }

        $comment = Comment::create([
            'ticket_id' => $ticket->id,
            'username' => $username,
            'content' => $validated['content'],
        ]);

        $comment->load('votes');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $comment->id,
                'username' => $comment->username,
                'content' => $comment->content,
                'formatted_content' => $this->commentFormatter->format($comment->content),
                'created_at' => $comment->created_at->toIso8601String(),
                'is_edited' => $comment->is_edited,
                'is_visible' => $comment->is_visible,
                'up_votes' => $comment->up_votes,
                'down_votes' => $comment->down_votes,
            ],
        ]);
    }

    public function update(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $username = session('username');

        if ($comment->username !== $username) {
            return response()->json([
                'success' => false,
                'message' => 'Sie können nur Ihre eigenen Kommentare bearbeiten.',
            ], 403);
        }

        $comment->update([
            'content' => $validated['content'],
            'is_edited' => true,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'formatted_content' => $this->commentFormatter->format($comment->content),
                'is_edited' => $comment->is_edited,
                'updated_at' => $comment->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function toggleVisibility(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        // Prevent toggling visibility on closed or archived tickets
        $ticket = $comment->ticket()->with('status')->first();

        if ($ticket->status->is_closed || $ticket->status->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Kommentare in geschlossenen oder archivierten Tickets können nicht geändert werden.',
            ], 403);
        }

        $username = session('username', 'System');

        $updateData = [
            'is_visible' => $validated['is_visible'],
        ];

        if (!$validated['is_visible']) {
            $updateData['hidden_by'] = $username;
            $updateData['hidden_at'] = now();
        } else {
            $updateData['hidden_by'] = null;
            $updateData['hidden_at'] = null;
        }

        $comment->update($updateData);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $comment->id,
                'is_visible' => $comment->is_visible,
                'hidden_by' => $comment->hidden_by,
                'hidden_at' => $comment->hidden_at?->toIso8601String(),
            ],
        ]);
    }
}
