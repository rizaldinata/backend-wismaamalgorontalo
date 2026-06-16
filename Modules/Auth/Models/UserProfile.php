<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'id_card_number',
        'phone_number',
        'gender',
        'job',
        'address_ktp',
        'emergency_contact_name',
        'emergency_contact_phone',
        'ktp_photo_path',
    ];

    protected $appends = ['ktp_photo_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getKtpPhotoUrlAttribute(): ?string
    {
        if ($this->ktp_photo_path && Storage::disk('public')->exists($this->ktp_photo_path)) {
            return url('storage-access/'.$this->ktp_photo_path);
        }

        return null;
    }
}
