<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SaveReportIssueRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    private function lang(): string
    {
        return $this->header('Accept-Language') === 'ar' ? 'ar' : 'en';
    }

    public function rules(): array
    {
        return [
            'issue_type' => ['required', 'string', 'max:191'],
            'message'    => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:6144'],
        ];
    }

    public function messages(): array
    {
        $ar = $this->lang() === 'ar';
        return [
            'issue_type.required' => $ar ? 'نوع المشكلة مطلوب.'          : 'Issue type is required.',
            'issue_type.string'   => $ar ? 'نوع المشكلة يجب أن يكون نصاً.' : 'Issue type must be a string.',
            'issue_type.max'      => $ar ? 'نوع المشكلة طويل جداً.'       : 'Issue type is too long.',
            'message.required'    => $ar ? 'الرسالة مطلوبة.'              : 'Message is required.',
            'message.string'      => $ar ? 'الرسالة يجب أن تكون نصاً.'    : 'Message must be a string.',
            'attachment.file'     => $ar ? 'المرفق يجب أن يكون ملفاً.'    : 'Attachment must be a file.',
            'attachment.mimes'    => $ar ? 'صيغة الملف غير مدعومة.'       : 'File type not supported.',
            'attachment.max'      => $ar ? 'حجم الملف كبير جداً (6MB كحد أقصى).' : 'File too large (max 6MB).',
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