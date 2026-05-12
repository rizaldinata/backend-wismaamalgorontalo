<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Inventory\Enums\ItemCondition;

class UpdateInventoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantitiy' => ['sometimes', 'required', 'integer', 'min:1'],
            'condition' => ['sometimes', 'required'],
            new Enum(ItemCondition::class),
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }


    public function authorize(): bool
    {
        return true;
    }
}
