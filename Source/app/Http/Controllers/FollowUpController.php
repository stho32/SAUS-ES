<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $filterCategory = $request->input('filter', 'in_bearbeitung');
        $today = now()->toDateString();

        // Exclude closed/archived statuses for follow-up unless explicitly filtered
        $query = Ticket::with('status')
            ->withVoteCounts()
            ->where('do_not_track', false);

        // Filter by status category
        if ($filterCategory && $filterCategory !== 'alle') {
            $query->whereHas('status', function ($q) use ($filterCategory) {
                $q->where('filter_category', $filterCategory);
            });
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('assignee', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        // Tickets with follow-up date <= today OR tickets without recent activity
        $query->where(function ($q) use ($today) {
            $q->where('follow_up_date', '<=', $today)
                ->orWhereNull('follow_up_date');
        });

        // Add last activity subquery for sorting
        $query->addSelect([
            'tickets.*',
            'last_comment_at' => Comment::selectRaw('MAX(created_at)')
                ->whereColumn('comments.ticket_id', 'tickets.id'),
        ]);

        // Sort by follow-up priority: overdue first, then today, then future, then no date
        $query->orderByRaw("
            CASE
                WHEN follow_up_date IS NOT NULL AND follow_up_date < ? THEN 0
                WHEN follow_up_date IS NOT NULL AND follow_up_date = ? THEN 1
                WHEN follow_up_date IS NOT NULL AND follow_up_date > ? THEN 2
                ELSE 3
            END ASC,
            follow_up_date ASC,
            COALESCE(
                (SELECT MAX(c.created_at) FROM comments c WHERE c.ticket_id = tickets.id),
                tickets.created_at
            ) ASC
        ", [$today, $today, $today]);

        $tickets = $query->paginate(25)->appends($request->query());

        $statusCategories = [
            'in_bearbeitung' => 'In Bearbeitung',
            'zurueckgestellt' => 'Zurückgestellt',
            'alle' => 'Alle',
        ];

        return view('follow-up.index', compact(
            'tickets',
            'search',
            'filterCategory',
            'statusCategories',
            'today',
        ));
    }
}
