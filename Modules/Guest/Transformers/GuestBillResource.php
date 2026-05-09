<?php

namespace Modules\Guest\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'bill_number'       => $this->bill_number,
            'amount'            => (float) $this->amount,
            'status'            => $this->status?->value,
            'status_label'      => $this->status?->label(),
            'payment_method'    => $this->payment_method,
            'payment_proof_url' => $this->payment_proof_url,
            'snap_token'        => $this->snap_token,
            'admin_notes'       => $this->admin_notes,
            'paid_at'           => $this->paid_at?->toDateTimeString(),
        ];
    }
}
