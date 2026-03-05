<?php

namespace Modules\Resident\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'id_card_number' => $this->id_card_number,
            'phone_number' => $this->phone_number,
            'gender' => $this->gender,
            'job' => $this->job,
            'address_ktp' => $this->address_ktp,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'ktp_photo_url' => $this->ktp_photo_path ? url('storage/' . $this->ktp_photo_path) : null,
        ];
    }
}
