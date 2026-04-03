<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentVote extends Model
{
    protected $table = 'comment_votes';

    const UPDATED_AT = null;

    protected $fillable = ['comment_id', 'username', 'value'];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
