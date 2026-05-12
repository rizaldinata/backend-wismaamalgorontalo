<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:1'],
            'expense_date' => ['sometimes', 'required', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
