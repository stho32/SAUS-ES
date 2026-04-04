<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'ticket_number', 'title', 'description', 'ki_summary', 'ki_interim',
        'status_id', 'assignee', 'show_on_website', 'public_comment',
        'affected_neighbors', 'follow_up_date', 'do_not_track', 'secret_string',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'show_on_website' => 'boolean',
            'do_not_track' => 'boolean',
            'follow_up_date' => 'date',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'affected_neighbors' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->secret_string)) {
                $ticket->secret_string = Str::random(50);
            }
        });
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ticketVotes(): HasMany
    {
        return $this->hasMany(TicketVote::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function contactPersons(): BelongsToMany
    {
        return $this->belongsToMany(ContactPerson::class, 'ticket_contact_persons')
            ->withPivot('created_at');
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    public function getUpVotesAttribute(): int
    {
        return $this->ticketVotes()->where('value', 'up')->count();
    }

    public function getDownVotesAttribute(): int
    {
        return $this->ticketVotes()->where('value', 'down')->count();
    }

    public function getUpvotersAttribute(): string
    {
        return $this->ticketVotes()->where('value', 'up')
            ->pluck('username')->implode(', ');
    }

    public function getDownvotersAttribute(): string
    {
        return $this->ticketVotes()->where('value', 'down')
            ->pluck('username')->implode(', ');
    }

    public function getLastActivityAttribute(): ?\DateTime
    {
        $lastComment = $this->comments()->max('created_at');
        return $lastComment ? new \DateTime($lastComment) : $this->created_at;
    }

    public function scopeShowOnWebsite($query)
    {
        return $query->where('show_on_website', true);
    }

    public function scopeWithVoteCounts($query)
    {
        return $query->withCount([
            'ticketVotes as up_votes_count' => fn($q) => $q->where('value', 'up'),
            'ticketVotes as down_votes_count' => fn($q) => $q->where('value', 'down'),
        ]);
    }
}
