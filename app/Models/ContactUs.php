<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ContactUs extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    // ─── Media Collection Names ───────────────────────────────────────────────
    public $attachmentCollection = 'attachment';

    // ─── Table & Fillable ─────────────────────────────────────────────────────
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

    // ─── Appends ──────────────────────────────────────────────────────────────
    protected $appends = [
        'attachment_files',
    ];

    // ─── Accessors ────────────────────────────────────────────────────────────


    public function getAttachmentFilesAttribute(): array
    {
        return $this->getMedia($this->attachmentCollection)
            ->map(fn($media) => [
                'url'       => $media->getUrl(),
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'extension' => strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION)),
            ])
            ->toArray();
    }
}