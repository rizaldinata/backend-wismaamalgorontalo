<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WaiveFineRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'waive_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
