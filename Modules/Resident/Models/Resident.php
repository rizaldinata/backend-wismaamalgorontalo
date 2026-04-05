<?php

namespace Modules\Resident\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Resident\database\factories\ResidentFactory;

class Resident extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return  $this->belongsTo(User::class);
    }

    protected static function newFactory()
    {
        return ResidentFactory::new();
    }
}
