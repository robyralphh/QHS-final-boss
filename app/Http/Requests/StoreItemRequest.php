<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id' => 'required|integer|exists:equipment,id',
            'condition'    => 'sometimes|in:New,Good,Fair,Poor,Damaged,Missing,Under Repair',
            'isBorrowed'   => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_id.exists' => 'The selected equipment does not exist.',
            'status.in'           => 'Status must be available, damaged, missing, or under_repair.',
        ];
    }
}