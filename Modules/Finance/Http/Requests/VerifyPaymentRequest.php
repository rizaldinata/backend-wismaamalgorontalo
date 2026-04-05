<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'is_approved' => ['required', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ];
    }


    public function authorize(): bool
    {
        return true;
    }
}
