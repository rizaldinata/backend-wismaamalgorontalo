<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMidtransFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bearer'                    => 'required|in:merchant,customer',
            'fees'                      => 'required|array',
            'fees.bank_transfer'        => 'required|array',
            'fees.bank_transfer.type'   => 'required|in:flat',
            'fees.bank_transfer.amount' => 'required|numeric|min:0',
            'fees.gopay'                => 'required|array',
            'fees.gopay.type'           => 'required|in:percent',
            'fees.gopay.rate'           => 'required|numeric|min:0|max:100',
            'fees.qris'                 => 'required|array',
            'fees.qris.type'            => 'required|in:percent',
            'fees.qris.rate'            => 'required|numeric|min:0|max:100',
            'fees.shopeepay'            => 'required|array',
            'fees.shopeepay.type'       => 'required|in:percent',
            'fees.shopeepay.rate'       => 'required|numeric|min:0|max:100',
            'fees.dana'                 => 'required|array',
            'fees.dana.type'            => 'required|in:percent',
            'fees.dana.rate'            => 'required|numeric|min:0|max:100',
            'fees.ovo'                  => 'required|array',
            'fees.ovo.type'             => 'required|in:percent',
            'fees.ovo.rate'             => 'required|numeric|min:0|max:100',
            'fees.linkaja'              => 'required|array',
            'fees.linkaja.type'         => 'required|in:percent',
            'fees.linkaja.rate'         => 'required|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'bearer.required' => 'Pihak yang menanggung biaya wajib diisi.',
            'bearer.in'       => 'Pihak yang menanggung biaya harus merchant atau customer.',
            'fees.required'   => 'Konfigurasi biaya wajib diisi.',
            '*.type.in'       => 'Tipe biaya tidak valid.',
            '*.amount.numeric'=> 'Nominal biaya harus berupa angka.',
            '*.rate.numeric'  => 'Persentase biaya harus berupa angka.',
            '*.rate.max'      => 'Persentase biaya tidak boleh melebihi 100%.',
        ];
    }
}
