<?php

namespace Modules\Rental\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Rental\Enums\RentalType;

class StoreLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'start_date' => 'required|date|after_or_equal:today',
            'duration' => 'required|integer|min:1|max:365',
            'rental_type' => ['required', Rule::enum(RentalType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Tanggal mulai sewa tidak boleh di masa lalu.',
        ];
    }
}
