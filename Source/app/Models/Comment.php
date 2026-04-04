<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $table = 'comments';

    protected $fillable = [
        'ticket_id', 'username', 'content', 'is_visible',
        'hidden_by', 'hidden_at', 'is_edited',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_edited' => 'boolean',
            'hidden_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CommentVote::class);
    }

    public function getUpVotesAttribute(): int
    {
        return $this->votes()->where('value', 'up')->count();
    }

    public function getDownVotesAttribute(): int
    {
        return $this->votes()->where('value', 'down')->count();
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
