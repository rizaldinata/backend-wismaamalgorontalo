<?php

namespace Modules\Finance\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'status'              => $this->status,
            'is_refund_eligible'  => $this->is_refund_eligible,
            'bank_name'           => $this->bank_name,
            'account_number'      => $this->account_number,
            'account_holder_name' => $this->account_holder_name,
            'refund_amount'       => (float) $this->refund_amount,
            'admin_fee'           => $this->admin_fee !== null ? (float) $this->admin_fee : null,
            'proof_url'           => $this->proof_url,
            'admin_notes'         => $this->admin_notes,
            'processed_at'        => $this->processed_at?->toIso8601String(),
            'schedule'            => [
                'id'         => $this->schedule?->id,
                'start_date' => $this->schedule?->start_date,
                'end_date'   => $this->schedule?->end_date,
                'room'       => $this->schedule?->room?->number,
            ],
            'payment' => [
                'id'             => $this->payment?->id,
                'invoice_number' => $this->payment?->invoice?->invoice_number,
                'payment_method' => is_object($this->payment?->payment_method)
                    ? $this->payment->payment_method->value
                    : $this->payment?->payment_method,
                'tenant_name' => $this->payment?->invoice?->tenant_name,
                'tenant_phone' => $this->payment?->invoice?->tenant_phone,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
