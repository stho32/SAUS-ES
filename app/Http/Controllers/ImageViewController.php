<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImageViewController extends Controller
{
    public function show(Request $request, string $code): View
    {
        $ticket = Ticket::where('secret_string', $code)->first();

        if (!$ticket) {
            abort(404, 'Ungültiger Zugriffscode.');
        }

        $attachments = $ticket->attachments()
            ->whereIn('file_type', [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ])
            ->orderBy('upload_date', 'asc')
            ->get();

        return view('imageview.show', compact('ticket', 'attachments', 'code'));
    }
}
