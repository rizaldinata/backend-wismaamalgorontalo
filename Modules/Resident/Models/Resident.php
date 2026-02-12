<?php

namespace Modules\Resident\Models;

use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resident extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return  $this->belongsTo(User::class);
    }
}
