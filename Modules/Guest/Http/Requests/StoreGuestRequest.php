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
            'check_out_at.after' => 'Tanggal keluar harus setelah tanggal masuk.',
        ];
    }
}
