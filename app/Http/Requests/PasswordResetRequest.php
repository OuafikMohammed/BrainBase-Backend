<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordResetRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6|regex:/^[0-9]+$/',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain at least one special character
                'confirmed'
            ],
            'password_confirmation' => 'required|string'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'email.exists' => 'No account found with this email address',
            'code.required' => 'Reset code is required',
            'code.size' => 'Reset code must be 6 digits',
            'code.regex' => 'Reset code must contain only numbers',
            'password.required' => 'New password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.regex' => 'Password must include at least one uppercase letter, one lowercase letter, one number, and one special character',
            'password.confirmed' => 'Passwords do not match',
            'password_confirmation.required' => 'Please confirm your new password'
        ];
    }
}