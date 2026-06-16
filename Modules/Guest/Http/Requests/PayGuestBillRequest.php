<?php

namespace Modules\Guest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayGuestBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:manual,midtrans'],
            'payment_proof' => [
                'required_if:payment_method,manual',
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'payment_proof.required_if' => 'Bukti pembayaran wajib diunggah untuk metode manual.',
            'payment_proof.file' => 'File bukti pembayaran tidak valid.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berformat JPG, JPEG, PNG, atau PDF.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ];
    }
}
