<?php

namespace Modules\Resident\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminResidentDetailResource extends JsonResource
{
    public function toArray($request)
    {
        $leaseStatus = $this->status?->value ?? $this->status;
        $paymentProof = $this->payment_proof ?? $this->payment_proof_path ?? null;

        return [
            'id' => (string) $this->id,
            'status' => $leaseStatus ?? '-',
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'finished_at' => $this->finished_at,
            'payment_proof_url' => $paymentProof ? url('storage/' . $paymentProof) : null,
            'room' => [
                'id' => $this->room?->id,
                'number' => $this->room?->number,
                'title' => $this->room?->title,
                'price' => $this->room?->price,
            ],
            'resident' => [
                'id' => $this->resident?->id,
                'name' => $this->resident?->user?->name,
                'email' => $this->resident?->user?->email,
                'id_card_number' => $this->resident?->id_card_number,
                'phone_number' => $this->resident?->phone_number,
                'gender' => $this->resident?->gender,
                'job' => $this->resident?->job,
                'address_ktp' => $this->resident?->address_ktp,
                'emergency_contact_name' => $this->resident?->emergency_contact_name,
                'emergency_contact_phone' => $this->resident?->emergency_contact_phone,
                'ktp_photo_url' => $this->resident?->ktp_photo_path ? url('storage/' . $this->resident->ktp_photo_path) : null,
            ],
        ];
    }
}
