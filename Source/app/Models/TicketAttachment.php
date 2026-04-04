<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $table = 'ticket_attachments';

    public $timestamps = false;

    protected $fillable = [
        'ticket_id', 'filename', 'original_filename', 'file_type',
        'file_size', 'uploaded_by', 'upload_date',
    ];

    protected function casts(): array
    {
        return [
            'upload_date' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
