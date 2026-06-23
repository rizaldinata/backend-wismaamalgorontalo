<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Setting\Services\SettingService;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedCodes = array_keys(SettingService::midtransMethodCatalog());

        return [
            'enabled_methods'   => 'present|array|min:1',
            'enabled_methods.*' => ['string', 'in:'.implode(',', $allowedCodes)],
        ];
    }

    public function messages(): array
    {
        return [
            'enabled_methods.present'     => 'Field enabled_methods wajib disertakan.',
            'enabled_methods.min'         => 'Minimal satu metode pembayaran Midtrans harus diaktifkan.',
            'enabled_methods.*.in'        => 'Metode pembayaran :input tidak dikenali atau tidak diizinkan.',
        ];
    }
}
