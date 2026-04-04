<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title', 'content', 'image_filename', 'event_date', 'created_by',
    ];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }
}
