<?php

namespace Modules\Resident\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResidentProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_card_number' => 'required|string|max:20',
            'phone_number' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'job' => 'nullable|string',
            'address_ktp' => 'required|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'ktp_photo' => 'nullable|image|max:2048',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
