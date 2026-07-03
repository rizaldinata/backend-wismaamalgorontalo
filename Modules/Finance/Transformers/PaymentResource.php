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
            'invoice_number' => $this->invoice->invoice_number ?? null,
            'invoice_type' => is_object($this->invoice?->type) ? $this->invoice->type->value : $this->invoice?->type,
            'amount' => (float) ($this->invoice->amount ?? 0),
            'midtrans_fee' => (int) ($this->midtrans_fee ?? 0),
            'fee_bearer' => $this->fee_bearer,
            'gross_amount' => (float) ($this->invoice->amount ?? 0)
                + ($this->fee_bearer === 'customer' ? (int) ($this->midtrans_fee ?? 0) : 0),
            'resident_name' => $this->invoice?->tenant_name,
            'room_number' => $this->invoice?->room_number,
            'payment_method' => is_object($this->payment_method) ? $this->payment_method->value : $this->payment_method,
            'payment_proof_url' => $this->payment_proof_path ? url('/storage/'.$this->payment_proof_path) : null,
            'transaction_id' => $this->transaction_id,
            'status' => is_object($this->status) ? $this->status->value : $this->status,
            'snap_token' => $this->snap_token,
            'payment_data' => $this->payment_data,
            'admin_notes' => $this->admin_notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
