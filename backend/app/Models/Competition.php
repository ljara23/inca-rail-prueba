<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competition extends Model
{
    protected $fillable = [
        'external_id', 'name', 'code',
        'area_name', 'emblem', 'season', 'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'competition_team');
    }
}
