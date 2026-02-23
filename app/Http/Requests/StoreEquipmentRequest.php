<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'sometimes|nullable|max:255',
            'condition' => 'required|string|max:255',
            'laboratory_id' => 'required|int',
            'category_ids' => 'sometimes|array', // Array of category IDs
            'category_ids.*' => 'integer|exists:categories,id', // Each ID must exist in categories table
        ];
    }
}