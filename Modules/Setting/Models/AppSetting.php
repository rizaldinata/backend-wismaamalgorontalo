<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public function getParsedValueAttribute()
    {
        if ($this->value === 'true') {
            return true;
        }
        if ($this->value === 'false') {
            return false;
        }

        return $this->value;
    }
}
