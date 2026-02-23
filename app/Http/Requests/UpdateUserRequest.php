<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule; // Import the Rule class

class UpdateUserRequest extends FormRequest
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
        if(request()->isMethod('post')) {
            return [
                'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user->id), // Ignore the current users email
            ],
            'role' => 'sometimes|string|max:255',
            'isActive' => 'sometimes|boolean',
            'avatar' => 'sometimes | nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'password' => [
                'sometimes',
                Password::min(8)->letters(),
            ],
            ];
        } else {
            return [
               'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user->id), // Ignore the current users email
            ],
            'role' => 'sometimes|string|max:255',
            'isActive' => 'sometimes|boolean',
            'avatar' => ' sometimes | nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'password' => [
                'sometimes',
                Password::min(8)->letters(),
            ],
            ];
        }
        
    }
}
