<?php

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'technician_name' => ['sometimes', 'required', 'string', 'max:255'],
            'location'        => ['sometimes', 'required', 'string', 'max:255'],
            'type'            => ['sometimes', 'required', 'in:pembersihan,perawatan'],
            'subtype'         => ['sometimes', 'required', 'in:rutin,deep_cleaning,darurat,perbaikan,maintenance'],
            'status'          => ['sometimes', 'required', 'in:in_progress,done,cancelled'],
            'notes'           => ['nullable', 'string'],
            'start_time'      => ['sometimes', 'required', 'date'],
            'end_time'        => ['nullable', 'date', 'after:start_time'],
        ];
    }
}
