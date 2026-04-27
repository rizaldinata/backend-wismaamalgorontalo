<?php

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'technician_name' => ['required', 'string', 'max:255'],
            'location'        => ['required', 'string', 'max:255'],
            'type'            => ['required', 'in:pembersihan,perawatan'],
            'subtype'         => ['required', 'in:rutin,deep_cleaning,darurat,perbaikan,maintenance'],
            'status'          => ['required', 'in:in_progress,done,cancelled'],
            'notes'           => ['nullable', 'string'],
            'start_time'      => ['required', 'date'],
            'end_time'        => ['nullable', 'date', 'after:start_time'],
        ];
    }
}
