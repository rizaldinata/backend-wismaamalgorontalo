<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureToggle extends Model
{
    protected $fillable = [
        'parent_id',
        'key',
        'name',
        'description',
        'icon',
        'is_active',
        'is_locked',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    /**
     * Parent module (null jika ini adalah module-level toggle).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FeatureToggle::class, 'parent_id');
    }

    /**
     * Child features di bawah module ini.
     */
    public function children(): HasMany
    {
        return $this->hasMany(FeatureToggle::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Audit logs untuk toggle ini.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(FeatureToggleLog::class, 'toggle_id');
    }

    /**
     * Cek apakah toggle ini adalah module-level (bukan feature).
     */
    public function isModule(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Status efektif: is_active AND parent.is_active (jika punya parent).
     */
    public function getEffectiveStatusAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->parent_id && $this->relationLoaded('parent') && $this->parent) {
            return $this->parent->is_active;
        }

        return $this->is_active;
    }
}
