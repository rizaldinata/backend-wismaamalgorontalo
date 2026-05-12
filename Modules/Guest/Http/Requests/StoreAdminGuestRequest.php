<?php

namespace Modules\Guest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Guest\Enums\GuestRelationship;
use Modules\Rental\Enums\LeaseStatus;

class StoreAdminGuestRequest extends FormRequest
{
    public function rules(): array
    {
        $relationships = implode(',', array_column(GuestRelationship::cases(), 'value'));
        $activeStatus = LeaseStatus::ACTIVE->value;

        return [
            'lease_id'     => "required|integer|exists:leases,id,status,{$activeStatus}",
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
