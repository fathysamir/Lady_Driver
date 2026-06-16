<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordRequest extends FormRequest
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
            'old_password' => 'required|string',
            'password'     => 'required|string|confirmed|min:8',
        ];
    }

    public function messages(): array
    {
        $ar = $this->lang() === 'ar';

        return [
            'old_password.required' => $ar ? 'كلمة المرور القديمة مطلوبة.' : 'Old password is required.',
            'password.required'     => $ar ? 'كلمة المرور الجديدة مطلوبة.' : 'New password is required.',
            'password.confirmed'    => $ar ? 'تأكيد كلمة المرور غير متطابق.' : 'Password confirmation does not match.',
            'password.min'          => $ar ? 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.' : 'Password must be at least 8 characters.',
        ];
    }

    // ✅ Covers business logic scenarios INSIDE the request
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ar   = $this->lang() === 'ar';
            $user = auth()->user();

            // Only run these checks if base rules already passed
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Scenario 1: old password is wrong
            if (! Hash::check($this->old_password, $user->password)) {
                $validator->errors()->add(
                    'old_password',
                    $ar ? 'كلمة المرور القديمة غير صحيحة.' : 'The old password is incorrect.'
                );
            }

            // Scenario 2: new password is same as old
            if (Hash::check($this->password, $user->password)) {
                $validator->errors()->add(
                    'password',
                    $ar ? 'كلمة المرور الجديدة يجب أن تختلف عن القديمة.' : 'The new password must be different from the old one.'
                );
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'data'    => null,
        ], 400));
    }
}