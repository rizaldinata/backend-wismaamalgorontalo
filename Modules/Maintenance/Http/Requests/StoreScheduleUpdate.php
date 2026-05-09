<?php

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Maintenance\Enums\ScheduleStatus;

class StoreScheduleUpdate extends FormRequest
{
    public function rules(): array
    {
        return [
            'notes' => ['required', 'string'],
            'status' => ['nullable', new Enum(ScheduleStatus::class)],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
