<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule; // Import the Rule class

class StoreUserRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users'), // Ignore the current users email
            ],
            'role' => 'required|string|max:255',
            'isActive' => 'sometimes|string',
            'avatar' => ' sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'password' => [
                'required',
                Password::min(8)->letters(),
            ],
            ];
    
    
    }
}
