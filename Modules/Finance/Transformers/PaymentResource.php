<?php

namespace Modules\Finance\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'payment_method' => $this->payment_method,
            'payment_proof_url' => $this->payment_proof_path ? url('/storage/' . $this->payment_proof_path) : null,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status->value,
            'admin_notes' => $this->admin_notes,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
