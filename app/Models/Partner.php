<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partner extends Model
{
    protected $table = 'partners';

    const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id', 'partner_name', 'partner_link', 'partner_list', 'is_master',
    ];

    protected function casts(): array
    {
        return ['is_master' => 'boolean'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
