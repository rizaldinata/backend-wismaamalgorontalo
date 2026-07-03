<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Finance\Enums\PaymentMethod;

class PerpanjangSewaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duration_months' => 'required|integer|min:1|max:12',
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_type' => 'nullable|string',
            'payment_proof' => 'required_if:payment_method,manual|image|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'duration_months.required' => 'Durasi perpanjangan wajib diisi',
            'duration_months.min' => 'Durasi minimal 1 bulan',
            'duration_months.max' => 'Durasi maksimal 12 bulan',
            'payment_method.required' => 'Metode pembayaran wajib dipilih',
            'payment_proof.required_if' => 'Bukti transfer wajib diunggah untuk pembayaran manual',
        ];
    }
}
