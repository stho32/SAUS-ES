<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SausNewsController extends Controller
{
    public function index(Request $request): View
    {
        // Default date range: last month to first Monday of current month
        $now = Carbon::now();
        $defaultFrom = $now->copy()->subMonth()->startOfMonth();
        $defaultTo = $now->copy()->startOfMonth()->next(Carbon::MONDAY);

        // If the first Monday is in the future beyond today, use today
        if ($defaultTo->greaterThan($now)) {
            $defaultTo = $now->copy();
        }

        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : $defaultFrom;

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : $defaultTo->endOfDay();

        // Get comments within the date range, grouped by ticket
        $comments = Comment::with('ticket', 'ticket.status')
            ->whereBetween('created_at', [$from, $to])
            ->where('username', '!=', 'System')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group comments by ticket
        $ticketGroups = $comments->groupBy('ticket_id')->map(function ($ticketComments) {
            return [
                'ticket' => $ticketComments->first()->ticket,
                'comments' => $ticketComments,
            ];
        })->sortByDesc(function ($group) {
            return $group['comments']->max('created_at');
        });

        return view('saus-news.index', compact('ticketGroups', 'from', 'to'));
    }
}
