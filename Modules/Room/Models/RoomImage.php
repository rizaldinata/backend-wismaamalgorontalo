<?php

namespace Modules\Room\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class RoomImage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['image_url', 'thumbnail_url'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // public function getImageUrlAttribute()
    // {
    //     return $this->image_path ? url('/storage-access/' . $this->image_path) : null;
    // }
    public function getImageUrlAttribute()
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            return url('/storage-access/' . $this->image_path);
        }

        return url('/storage-access/rooms/default.jpg');
    }

    // public function getThumbnailUrlAttribute()
    // {
    //     return $this->thumbnail_path ? url('/storage-access/' . $this->thumbnail_path) : $this->image_url;
    // }
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail_path && Storage::disk('public')->exists($this->thumbnail_path)) {
            return url('/storage-access/' . $this->thumbnail_path);
        }

        return url('/storage-access/rooms/default.jpg');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
            if ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path)) {
                Storage::disk('public')->delete($image->thumbnail_path);
            }
        });
    }
}
