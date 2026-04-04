<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    protected $table = 'ticket_status';

    const UPDATED_AT = null;

    protected $fillable = [
        'name', 'description', 'sort_order', 'is_active', 'is_archived',
        'is_closed', 'background_color', 'filter_category',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
            'is_closed' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
