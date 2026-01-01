<?php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\File;

class ValidPublicImage implements Rule
{
    public function __construct(private ?string $requiredIf = null) {}

    public function passes($attribute, $value): bool
    {
        // لو الحقل مش مطلوب أصلًا — تجاهله
        if ($this->requiredIf && ! request()->filled($this->requiredIf)) {
            return true;
        }

        if (! $value) return false;

        $value = urldecode($value);
        $value = strtok($value, '?');

        $path = public_path(ltrim($value, '/'));

        if (! \File::exists($path)) return false;

        $mime = \File::mimeType($path);

        return in_array($mime, [
            'image/jpg','image/jpeg','image/png','image/gif','image/webp'
        ]);
    }

    public function message(): string
    {
        return 'The :attribute must be a valid uploaded image.';
    }
}

