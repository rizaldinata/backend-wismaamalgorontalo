<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BayarDendaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fine_ids' => ['required', 'array', 'min:1'],
            'fine_ids.*' => ['integer', 'exists:fines,id'],
            'payment_method' => ['required', 'string', 'in:manual,midtrans'],
            'payment_proof' => ['nullable', 'file', 'image', 'max:5120'],
            'preferred_payment_type' => ['nullable', 'string'],
        ];
    }
}
