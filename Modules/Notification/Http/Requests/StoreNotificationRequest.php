<?php

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'target_phone' => ['required', 'string', 'max:20'],
            'message_body' => ['required', 'string', 'max:1000'],
        ];
    }
}