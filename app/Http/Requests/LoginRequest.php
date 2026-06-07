<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function lang(): string
    {
        return $this->header('Accept-Language', 'en');
    }

    public function rules(): array
    {
        return [
            'email'        => 'required|string',
            'password'     => 'required|string|min:8',
            'device_token' => 'nullable',
        ];
    }

    public function messages(): array
    {
        $ar = $this->lang() === 'ar';

        return [
            'email.required'    => $ar ? 'البريد الإلكتروني أو رقم الهاتف مطلوب.' : 'Email or phone is required.',
            'password.required' => $ar ? 'كلمة المرور مطلوبة.' : 'Password is required.',
            'password.min'      => $ar ? 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.' : 'Password must be at least 8 characters.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'data'    => null,
        ], 400));
    }
}