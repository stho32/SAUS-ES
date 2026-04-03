<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicTicketController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort', 'last_activity');
        $sortDir = $request->input('dir', 'desc');
        $showAll = $request->input('show_all') === '1';

        $query = Ticket::with('status')
            ->showOnWebsite()
            ->withVoteCounts()
            ->withCount('comments');

        // Hide tickets inactive for more than 3 months unless show_all
        if (!$showAll) {
            $threeMonthsAgo = Carbon::now()->subMonths(3);

            $query->where(function ($q) use ($threeMonthsAgo) {
                $q->where('created_at', '>=', $threeMonthsAgo)
                    ->orWhereHas('comments', function ($sq) use ($threeMonthsAgo) {
                        $sq->where('created_at', '>=', $threeMonthsAgo);
                    });
            });
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('public_comment', 'like', "%{$search}%")
                    ->orWhereHas('status', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $allowedSorts = ['id', 'title', 'status', 'last_activity'];

        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'status') {
                $query->join('ticket_status', 'tickets.status_id', '=', 'ticket_status.id')
                    ->orderBy('ticket_status.name', $sortDir)
                    ->select('tickets.*');
            } elseif ($sortBy === 'last_activity') {
                $query->addSelect([
                    'last_activity_at' => Comment::selectRaw('MAX(created_at)')
                        ->whereColumn('comments.ticket_id', 'tickets.id'),
                ])->orderByRaw("COALESCE(last_activity_at, tickets.created_at) {$sortDir}");
            } else {
                $query->orderBy($sortBy, $sortDir);
            }
        }

        $tickets = $query->paginate(25)->appends($request->query());

        return view('public.tickets.index', compact(
            'tickets',
            'search',
            'sortBy',
            'sortDir',
            'showAll',
        ));
    }
}
