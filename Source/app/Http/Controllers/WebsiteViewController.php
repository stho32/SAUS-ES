<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\View\View;

class WebsiteViewController extends Controller
{
    public function index(): View
    {
        $tickets = Ticket::with('status')
            ->showOnWebsite()
            ->withVoteCounts()
            ->withCount('comments')
            ->addSelect([
                'tickets.*',
                'last_activity_at' => Comment::selectRaw('MAX(created_at)')
                    ->whereColumn('comments.ticket_id', 'tickets.id'),
            ])
            ->orderByRaw('COALESCE(last_activity_at, tickets.created_at) DESC')
            ->get();

        return view('website-view.index', compact('tickets'));
    }
}
