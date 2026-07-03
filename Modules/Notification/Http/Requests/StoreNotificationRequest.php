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
        $hasUserId = $this->filled('user_id');

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'target_phone' => [$hasUserId ? 'nullable' : 'required', 'nullable', 'string', 'max:20'],
            'message_body' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('user_id') && ! $this->filled('target_phone')) {
                $validator->errors()->add('target_phone', 'Nomor telepon wajib diisi jika tidak memilih pengguna.');
            }
        });
    }
}
