<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\ValidPublicImage;

class DriverRegisterRequest extends FormRequest
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
            'name'                        => 'required|string|max:255',
            'email'                       => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at')
                ->where(fn($q) => $q->where('status', '!=', 'pending')),

            ],
            'password'                    => 'required|string|min:8|confirmed',
            'country_code'                => 'required|string|max:10',
            'phone'                       => [
                'required',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('country_code', $this->country_code)
                        ->whereNull('deleted_at')
                        ->where('status', '!=', 'pending');
                }),
            ],
            'image'                       => ['required', new ValidPublicImage],
            'birth_date'                  => [
                'required', 'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'),
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'city_id'                     => ['required', Rule::exists('cities', 'id')->whereNull('deleted_at')],
            'national_ID'                 => 'nullable|digits:14|required_without:passport_ID',
            'ID_front_image'              => ['required_with:national_ID', new ValidPublicImage('national_ID')],
            'ID_back_image'               => ['required_with:national_ID', new ValidPublicImage('national_ID')],
            'passport_ID'                 => 'nullable|required_without:national_ID',
            'passport_image'              => ['required_with:passport_ID', new ValidPublicImage('passport_ID')],
            'driving_license_number'      => 'required|string|max:50',
            'license_expire_date'         => [
                'required', 'date_format:Y-m-d', 'after_or_equal:today',
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'license_front_image'         => ['required', new ValidPublicImage],
            'license_back_image'          => ['required', new ValidPublicImage],
            'vehicle_type'                => ['required', Rule::in(['car', 'scooter'])],
            'car_mark_id'                 => ['required_if:vehicle_type,car', 'nullable', Rule::exists('car_marks', 'id')],
            'car_model_id'                => ['required_if:vehicle_type,car', 'nullable', Rule::exists('car_models', 'id')],
            'scooter_mark_id'             => ['required_if:vehicle_type,scooter', 'nullable', Rule::exists('motorcycle_marks', 'id')],
            'scooter_model_id'            => ['required_if:vehicle_type,scooter', 'nullable', Rule::exists('motorcycle_models', 'id')],
            'air_conditioned'             => 'nullable|boolean',
            'allow_pets'                  => 'nullable|boolean',
            'color'                       => 'required|string|max:255',
            'year'                        => 'required|integer|min:1990|max:' . date('Y'),
            'plate_num'                   => 'required|string|max:255',
            'vehicle_image'               => ['required', new ValidPublicImage],
            'plate_image'                 => ['required', new ValidPublicImage],
            'vehicle_license_expire_date' => [
                'required', 'date_format:Y-m-d', 'after_or_equal:today',
                'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
            ],
            'vehicle_license_front_image' => ['required', new ValidPublicImage],
            'vehicle_license_back_image'  => ['required', new ValidPublicImage],
            'registration_id'             => 'required',
        ];
    }

    public function messages(): array
    {
        $ar = $this->lang() === 'ar';

        return [
            'name.required'                           => $ar ? 'الاسم مطلوب.' : 'Name is required.',
            'email.required'                          => $ar ? 'البريد الإلكتروني مطلوب.' : 'Email is required.',
            'email.email'                             => $ar ? 'صيغة البريد الإلكتروني غير صحيحة.' : 'Invalid email format.',
            'email.unique'                            => $ar ? 'هذا البريد الإلكتروني مسجل بالفعل.' : 'This email address is already registered.',
            'password.required'                       => $ar ? 'كلمة المرور مطلوبة.' : 'Password is required.',
            'password.min'                            => $ar ? 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.' : 'Password must be at least 8 characters.',
            'password.confirmed'                      => $ar ? 'كلمة المرور غير متطابقة.' : 'Password confirmation does not match.',
            'phone.required'                          => $ar ? 'رقم الهاتف مطلوب.' : 'Phone is required.',
            'phone.unique'                            => $ar ? 'هذا الرقم مسجل بالفعل.' : 'This phone number is already registered.',
            'birth_date.required'                     => $ar ? 'تاريخ الميلاد مطلوب.' : 'Birth date is required.',
            'birth_date.before_or_equal'              => $ar ? 'يجب أن يكون عمرك 16 سنة على الأقل.' : 'You must be at least 16 years old.',
            'city_id.required'                        => $ar ? 'المدينة مطلوبة.' : 'City is required.',
            'national_ID.digits'                      => $ar ? 'الرقم القومي يجب أن يكون 14 رقماً.' : 'National ID must be 14 digits.',
            'national_ID.required_without'            => $ar ? 'الرقم القومي أو جواز السفر مطلوب.' : 'National ID or Passport is required.',
            'passport_ID.required_without'            => $ar ? 'جواز السفر أو الرقم القومي مطلوب.' : 'Passport or National ID is required.',
            'driving_license_number.required'         => $ar ? 'رقم رخصة القيادة مطلوب.' : 'Driving license number is required.',
            'license_expire_date.required'            => $ar ? 'تاريخ انتهاء الرخصة مطلوب.' : 'License expiry date is required.',
            'license_expire_date.after_or_equal'      => $ar ? 'رخصة القيادة منتهية.' : 'Driving license is expired.',
            'vehicle_type.required'                   => $ar ? 'نوع المركبة مطلوب.' : 'Vehicle type is required.',
            'color.required'                          => $ar ? 'اللون مطلوب.' : 'Color is required.',
            'year.required'                           => $ar ? 'سنة الصنع مطلوبة.' : 'Year is required.',
            'plate_num.required'                      => $ar ? 'رقم اللوحة مطلوب.' : 'Plate number is required.',
            'vehicle_license_expire_date.required'    => $ar ? 'تاريخ انتهاء رخصة المركبة مطلوب.' : 'Vehicle license expiry date is required.',
            'vehicle_license_expire_date.after_or_equal' => $ar ? 'رخصة المركبة منتهية.' : 'Vehicle license is expired.',
            'registration_id.required'                => $ar ? 'رقم التسجيل مطلوب.' : 'Registration ID is required.',
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