<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureToggleLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'toggle_id',
        'user_id',
        'old_value',
        'new_value',
    ];

    protected $casts = [
        'old_value' => 'boolean',
        'new_value' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Toggle yang dicatat perubahannya.
     */
    public function toggle(): BelongsTo
    {
        return $this->belongsTo(FeatureToggle::class, 'toggle_id');
    }
}
