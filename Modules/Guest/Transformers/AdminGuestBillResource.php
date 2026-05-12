<?php

namespace Modules\Guest\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminGuestBillResource extends JsonResource
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
            'created_at'        => $this->created_at?->toDateTimeString(),
            'guest'             => [
                'id'           => $this->guest?->id,
                'name'         => $this->guest?->name,
                'check_in_at'  => $this->guest?->check_in_at?->toDateTimeString(),
                'check_out_at' => $this->guest?->check_out_at?->toDateTimeString(),
                'total_days'   => $this->guest?->total_days,
                'billable_days' => $this->guest?->billable_days,
            ],
            'penghuni'          => $this->guest?->lease?->resident?->user?->name ?? '-',
            'kamar'             => $this->guest?->lease?->room?->number ?? '-',
        ];
    }
}
