<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterLink extends Model
{
    protected $table = 'master_links';

    const UPDATED_AT = null;

    protected $fillable = ['link_code', 'description', 'is_active', 'last_used_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
