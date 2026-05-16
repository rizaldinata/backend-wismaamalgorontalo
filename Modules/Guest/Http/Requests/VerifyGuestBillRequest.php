<?php

namespace Modules\Guest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyGuestBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_approved' => ['required', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_approved.required' => 'Status verifikasi wajib diisi.',
            'is_approved.boolean' => 'Status verifikasi harus bernilai true atau false.',
            'admin_notes.max' => 'Catatan admin maksimal 500 karakter.',
        ];
    }
}
