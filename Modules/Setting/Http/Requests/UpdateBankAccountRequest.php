<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name'            => 'sometimes|required|string|max:100',
            'account_number'       => 'sometimes|required|string|max:50',
            'account_holder'       => 'sometimes|required|string|max:100',
            'payment_instructions' => 'nullable|string|max:5000',
            'is_active'            => 'nullable|boolean',
            'sort_order'           => 'nullable|integer|min:0',
        ];
    }
}
