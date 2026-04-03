<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function index(): View
    {
        // Status distribution: count per status with colors
        $statusDistribution = TicketStatus::withCount('tickets')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($status) {
                return [
                    'name' => $status->name,
                    'count' => $status->tickets_count,
                    'color' => $status->background_color,
                ];
            });

        // Assignee workload: tickets in_bearbeitung grouped by assignee
        // Supporting multi-assignee split by comma/plus
        $inBearbeitungStatuses = TicketStatus::where('filter_category', 'in_bearbeitung')->pluck('id');

        $activeTickets = Ticket::whereIn('status_id', $inBearbeitungStatuses)
            ->whereNotNull('assignee')
            ->where('assignee', '!=', '')
            ->pluck('assignee');

        $assigneeWorkload = [];
        foreach ($activeTickets as $assigneeField) {
            // Split by comma or plus sign
            $assignees = preg_split('/[,+]/', $assigneeField);
            foreach ($assignees as $assignee) {
                $assignee = trim($assignee);
                if ($assignee === '') {
                    continue;
                }
                if (!isset($assigneeWorkload[$assignee])) {
                    $assigneeWorkload[$assignee] = 0;
                }
                $assigneeWorkload[$assignee]++;
            }
        }
        arsort($assigneeWorkload);

        // Completed per assignee: tickets in closed/archived statuses
        $closedStatuses = TicketStatus::where('is_closed', true)
            ->orWhere('is_archived', true)
            ->pluck('id');

        $closedTickets = Ticket::whereIn('status_id', $closedStatuses)
            ->whereNotNull('assignee')
            ->where('assignee', '!=', '')
            ->pluck('assignee');

        $completedPerAssignee = [];
        foreach ($closedTickets as $assigneeField) {
            $assignees = preg_split('/[,+]/', $assigneeField);
            foreach ($assignees as $assignee) {
                $assignee = trim($assignee);
                if ($assignee === '') {
                    continue;
                }
                if (!isset($completedPerAssignee[$assignee])) {
                    $completedPerAssignee[$assignee] = 0;
                }
                $completedPerAssignee[$assignee]++;
            }
        }
        arsort($completedPerAssignee);

        return view('statistics.index', compact(
            'statusDistribution',
            'assigneeWorkload',
            'completedPerAssignee',
        ));
    }
}
