<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|nullable',
            'location' => 'sometimes|string|nullable',
            'image' => [
                'sometimes',
                Rule::when($this->hasFile('image'), ['image', 'mimes:jpeg,png,jpg,gif', 'max:2048'], ['string']),
            ],
            'laboratory_id' => 'sometimes|exists:laboratory,id',
            'quantity' => 'sometimes|integer|min:0',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:categories,id',
            'isActive' => 'sometimes|boolean',
        ];
    }
}