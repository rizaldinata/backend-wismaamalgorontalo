<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFineRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_user_id' => ['required', 'integer', 'exists:users,id'],
            'amount'         => ['required', 'numeric', 'min:1000'],
            'reason'         => ['required', 'string', 'max:500'],
        ];
    }
}
