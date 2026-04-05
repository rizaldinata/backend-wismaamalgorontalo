<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        $permissionId = $this->route('id');

        return [
            'name' => 'required|string|unique:permissions,name,' . $permissionId,
            'target' => 'required|in:admin,user',
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
