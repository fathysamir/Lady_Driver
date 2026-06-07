<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SaveContactUsRequest extends FormRequest
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
            'subject'    => ['required', 'string', 'max:191'],
            'name'       => ['required', 'string', 'max:191'],
            'email'      => ['required', 'string', 'max:191', 'email'],
            'message'    => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:6144'],
        ];
    }

    public function messages(): array
    {
        $ar = $this->lang() === 'ar';

        return [
            'subject.required'    => $ar ? 'الموضوع مطلوب.'                                         : 'Subject is required.',
            'subject.string'      => $ar ? 'الموضوع يجب أن يكون نصاً.'                              : 'Subject must be a string.',
            'subject.max'         => $ar ? 'الموضوع يجب ألا يتجاوز 191 حرفاً.'                      : 'Subject may not be greater than 191 characters.',

            'name.required'       => $ar ? 'الاسم مطلوب.'                                           : 'Name is required.',
            'name.string'         => $ar ? 'الاسم يجب أن يكون نصاً.'                                : 'Name must be a string.',
            'name.max'            => $ar ? 'الاسم يجب ألا يتجاوز 191 حرفاً.'                        : 'Name may not be greater than 191 characters.',

            'email.required'      => $ar ? 'البريد الإلكتروني مطلوب.'                               : 'Email is required.',
            'email.email'         => $ar ? 'صيغة البريد الإلكتروني غير صحيحة.'                      : 'Invalid email format.',
            'email.max'           => $ar ? 'البريد الإلكتروني يجب ألا يتجاوز 191 حرفاً.'            : 'Email may not be greater than 191 characters.',

            'message.required'    => $ar ? 'الرسالة مطلوبة.'                                        : 'Message is required.',
            'message.string'      => $ar ? 'الرسالة يجب أن تكون نصاً.'                              : 'Message must be a string.',

            'attachment.file'     => $ar ? 'المرفق يجب أن يكون ملفاً صالحاً.'                       : 'The attachment must be a valid file.',
            'attachment.mimes'    => $ar ? 'المرفق يجب أن يكون من النوع: pdf, doc, docx, jpg, jpeg, png.' : 'The attachment must be of type: pdf, doc, docx, jpg, jpeg, png.',
            'attachment.max'      => $ar ? 'حجم المرفق يجب ألا يتجاوز 6 ميجابايت.'                  : 'The attachment may not be greater than 6 MB.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $errors->first(),
            'data'    => null,
        ], 400));
    }
}