<?php

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Maintenance\Enums\MaintenanceStatus;

class StoreMaintenanceUpdate extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'status' => ['nullable', 'string', Rule::enum(MaintenanceStatus::class)],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['file', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
