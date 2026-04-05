<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Finance\Enums\PaymentMethod;

class PayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_proof' => 'required_if:payment_method,manual|image|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_proof.required_if' => 'Bukti transfer wajib diunggah untuk pembayaran manual',
        ];
    }
}
