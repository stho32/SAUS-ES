<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketVote extends Model
{
    protected $table = 'ticket_votes';

    const UPDATED_AT = null;

    protected $fillable = ['ticket_id', 'username', 'value'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
