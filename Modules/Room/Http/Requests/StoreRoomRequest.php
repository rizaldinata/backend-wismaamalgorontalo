<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Room\Enums\RoomStatus;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'unique:rooms,number'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_daily' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(RoomStatus::class)],
            'description' => ['nullable', 'string'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string'],
        ];
    }
}
