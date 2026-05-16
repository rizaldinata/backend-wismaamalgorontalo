<?php

namespace Modules\Guest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Guest\Enums\GuestRelationship;

class StoreAdminGuestRequest extends FormRequest
{
    public function rules(): array
    {
        $relationships = implode(',', array_column(GuestRelationship::cases(), 'value'));

        return [
            'schedule_id'  => 'required|integer|exists:room_schedules,id,status,active,type,sewa',
            'name'         => 'required|string|max:255',
            'check_in_at'  => 'required|date',
            'check_out_at' => 'required|date|after:check_in_at',
            'relationship' => "required|string|in:{$relationships}",
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'lease_id.exists' => 'Penghuni belum memiliki sewa aktif.',
            'check_out_at.after' => 'Tanggal keluar harus setelah tanggal masuk.',
        ];
    }
}
