<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContactPerson extends Model
{
    protected $table = 'contact_persons';

    protected $fillable = [
        'name', 'email', 'phone', 'contact_notes',
        'responsibility_notes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_contact_persons')
            ->withPivot('created_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
