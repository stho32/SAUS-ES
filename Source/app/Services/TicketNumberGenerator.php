<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;

class TicketNumberGenerator
{
    public function generate(): string
    {
        $today = date('Ymd');
        $prefix = $today . '-';

        $lastTicket = Ticket::where('ticket_number', 'like', $prefix . '%')
            ->orderBy('ticket_number', 'desc')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->ticket_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
