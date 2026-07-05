<?php

namespace Modules\Guest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Guest\Enums\GuestRelationship;

class StoreGuestRequest extends FormRequest
{
    public function rules(): array
    {
        $relationships = implode(',', array_column(GuestRelationship::cases(), 'value'));

        return [
            'guests' => 'required|array|min:1|max:3',
            'guests.*.name' => 'required|string|max:255',
            'guests.*.relationship' => "required|string|in:{$relationships}",
            'guests.*.identity_image' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'check_in_at' => 'required|date',
            'check_out_at' => 'required|date|after:check_in_at',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'check_out_at.after' => 'Tanggal keluar harus setelah tanggal masuk.',
        ];
    }
}
