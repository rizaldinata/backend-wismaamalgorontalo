<?php

namespace Modules\Finance\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public static function collectionWithSchedule($resource)
    {
        $collection = parent::collection($resource);

        if (\App\Support\ModuleGate::isActive('Schedule')) {
            $scheduleIds = collect($resource)->pluck('schedule_id')->filter()->unique()->toArray();
            if (! empty($scheduleIds)) {
                $scheduleData = \Modules\Schedule\Services\ScheduleService::getByIds($scheduleIds);
                foreach ($resource as $item) {
                    $item->schedule_data = $scheduleData[$item->schedule_id] ?? null;
                }
            }
        }

        return $collection;
    }

    public static function makeWithSchedule($resource)
    {
        if (\App\Support\ModuleGate::isActive('Schedule') && $resource->schedule_id) {
            $scheduleData = \Modules\Schedule\Services\ScheduleService::getByIds([$resource->schedule_id]);
            $resource->schedule_data = $scheduleData[$resource->schedule_id] ?? null;
        }

        return parent::make($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'is_refund_eligible' => $this->is_refund_eligible,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'account_holder_name' => $this->account_holder_name,
            'refund_amount' => (float) $this->refund_amount,
            'admin_fee' => $this->admin_fee !== null ? (float) $this->admin_fee : null,
            'proof_url' => $this->proof_url,
            'admin_notes' => $this->admin_notes,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'schedule' => [
                'id' => $this->schedule_id,
                'start_date' => $this->schedule_data['start_date'] ?? null,
                'end_date' => $this->schedule_data['end_date'] ?? null,
                'room' => $this->schedule_data['room']['number'] ?? null,
            ],
            'payment' => [
                'id' => $this->payment?->id,
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
