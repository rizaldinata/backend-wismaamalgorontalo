<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Room\Enums\RoomStatus;

class UpdateRoomRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $roomId = $this->route('room') ?? $this->route('id');

        return [
            'title' => ['required', 'string', 'max:255'],
            'number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'number')->ignore($roomId),
            ],
            'price' => 'required|numeric|min:0',
            'status' => ['required', Rule::enum(RoomStatus::class)],
            'description' => 'nullable|string',
            'facilities' => 'nullable|array',
            'facilities.*' => 'string',
        ];
    }

    public function messages()
    {
        return [
            'number.unique' => 'Nomor kamar sudah digunakan.',
            'status.enum' => 'Status kamar tidak valid.',
        ];
    }
}
