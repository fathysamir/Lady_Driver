<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClientRegisterRequest extends FormRequest
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
            'name'         => 'required|string|max:255',
            'email'        => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),

            ],
            'password'     => 'required|string|min:8|confirmed',
            'country_code' => 'required|string|max:10',
            'phone'        => [
                'required',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('country_code', $this->country_code)
                        ->whereNull('deleted_at');
                }),
            ],
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'birth_date'   => [
                'required', 'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'city_id'      => ['nullable', Rule::exists('cities', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        $ar = $this->lang() === 'ar';

        return [
            'name.required'              => $ar ? 'الاسم مطلوب.' : 'Name is required.',
            'email.required'             => $ar ? 'البريد الإلكتروني مطلوب.' : 'Email is required.',
            'email.email'                => $ar ? 'صيغة البريد الإلكتروني غير صحيحة.' : 'Invalid email format.',
            'email.unique'               => $ar ? 'هذا البريد الإلكتروني مسجل بالفعل.' : 'This email address is already registered.',
            'password.required'          => $ar ? 'كلمة المرور مطلوبة.' : 'Password is required.',
            'password.min'               => $ar ? 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.' : 'Password must be at least 8 characters.',
            'password.confirmed'         => $ar ? 'كلمة المرور غير متطابقة.' : 'Password confirmation does not match.',
            'country_code.required'      => $ar ? 'كود الدولة مطلوب.' : 'Country code is required.',
            'phone.required'             => $ar ? 'رقم الهاتف مطلوب.' : 'Phone is required.',
            'phone.unique'               => $ar ? 'هذا الرقم مسجل بالفعل.' : 'This phone number is already registered.',
            'birth_date.required'        => $ar ? 'تاريخ الميلاد مطلوب.' : 'Birth date is required.',
            'birth_date.before_or_equal' => $ar ? 'يجب أن يكون عمرك 16 سنة على الأقل.' : 'You must be at least 16 years old.',
            'city_id.exists'             => $ar ? 'المدينة غير موجودة.' : 'City not found.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        if ($errors->has('email') && $errors->has('phone')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => [
                    'email' => $errors->first('email'),
                    'phone' => $errors->first('phone'),
                ],
                'data' => null,
            ], 400));
        }

        if ($errors->has('email')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $errors->first('email'),
                'data'    => null,
            ], 400));
        }

        if ($errors->has('phone')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $errors->first('phone'),
                'data'    => null,
            ], 400));
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $errors->first(),
            'data'    => null,
        ], 400));
    }
}