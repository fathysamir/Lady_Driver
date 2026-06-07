<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ContactUs extends Model
{
    use HasFactory, SoftDeletes;

    public $attachmentCollection = 'attachment';

    protected $table = 'contact_us';

    protected $fillable = [
        'subject',
        'name',
        'email',
        'message',
        'phone',
        'reply',
        'seen',
    ];

    protected $hidden = ['deleted_at'];

    protected $allowedSorts = [
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'attachment_files',
    ];

    public function getAttachmentFilesAttribute(): array
    {
        $rows = DB::table('media')
            ->where('attachmentable_id', $this->id)
            ->where('attachmentable_type', get_class($this))
            ->where('collection_name', $this->attachmentCollection)
            ->get();

        return $rows->map(function ($row) {
            $path = $row->path ?? $row->Path ?? null;
            $fileName = basename($path);
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            return [
                'url'       => asset($path),
                'file_name' => $fileName,
                'extension' => $extension,
            ];
        })->toArray();
    }
}