<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePerpanjangSewaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duration_months' => 'required|integer|min:1|max:12',
        ];
    }

    public function messages(): array
    {
        return [
            'duration_months.required' => 'Durasi perpanjangan wajib diisi',
            'duration_months.min'      => 'Durasi minimal 1 bulan',
            'duration_months.max'      => 'Durasi maksimal 12 bulan',
        ];
    }
}
