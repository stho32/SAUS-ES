<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Services\ActivityHelper;
use App\Services\CommentFormatter;
use App\Services\TicketNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketNumberGenerator $ticketNumberGenerator,
        private readonly CommentFormatter $commentFormatter,
    ) {}

    public function index(Request $request): View
    {
        $filterCategory = $request->input('filter', 'in_bearbeitung');
        $search = $request->input('search');
        $sortBy = $request->input('sort', 'last_activity');
        $sortDir = $request->input('dir', 'desc');

        $query = Ticket::with('status')
            ->withVoteCounts()
            ->withCount('comments');

        // Filter by status category
        if ($filterCategory && $filterCategory !== 'alle') {
            $query->whereHas('status', function ($q) use ($filterCategory) {
                $q->where('filter_category', $filterCategory);
            });
        }

        // Search across title, status name, assignee, and comments
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('assignee', 'like', "%{$search}%")
                    ->orWhereHas('status', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('comments', function ($sq) use ($search) {
                        $sq->where('content', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $allowedSorts = ['id', 'title', 'status', 'up_votes_count', 'down_votes_count', 'affected_neighbors', 'last_activity'];

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

        $statusCategories = [
            'in_bearbeitung' => 'In Bearbeitung',
            'zurueckgestellt' => 'Zurückgestellt',
            'geschlossen' => 'Geschlossen',
            'archiviert' => 'Archiviert',
            'alle' => 'Alle',
        ];

        return view('tickets.index', compact(
            'tickets',
            'filterCategory',
            'search',
            'sortBy',
            'sortDir',
            'statusCategories',
        ));
    }

    public function create(): View
    {
        $statuses = TicketStatus::active()->orderBy('sort_order')->get();

        return view('tickets.create', compact('statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status_id' => ['required', 'exists:ticket_status,id'],
        ]);

        $validated['ticket_number'] = $this->ticketNumberGenerator->generate();

        $ticket = Ticket::create($validated);

        $status = TicketStatus::find($validated['status_id']);

        Comment::create([
            'ticket_id' => $ticket->id,
            'username' => 'System',
            'content' => "Ticket erstellt mit Status: {$status->name}",
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket wurde erfolgreich erstellt.');
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'status',
            'comments' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'comments.votes',
            'attachments',
            'contactPersons',
        ]);

        $ticket->loadCount([
            'ticketVotes as up_votes_count' => fn($q) => $q->where('value', 'up'),
            'ticketVotes as down_votes_count' => fn($q) => $q->where('value', 'down'),
        ]);

        $formatter = $this->commentFormatter;
        $username = session('username');

        $userTicketVote = $ticket->ticketVotes()
            ->where('username', $username)
            ->first();

        return view('tickets.show', compact('ticket', 'formatter', 'username', 'userTicketVote'));
    }

    public function edit(Ticket $ticket): View
    {
        $statuses = TicketStatus::active()->orderBy('sort_order')->get();

        return view('tickets.edit', compact('ticket', 'statuses'));
    }

    public function email(Ticket $ticket): View
    {
        $ticket->load([
            'status',
            'comments' => function ($q) {
                $q->where('is_visible', true)->orderBy('created_at', 'asc');
            },
            'contactPersons',
        ]);

        return view('tickets.email', compact('ticket'));
    }
}
