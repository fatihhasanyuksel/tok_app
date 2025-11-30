<?php

namespace ToKLearningSpace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use ToKLearningSpace\Models\LsTemplate;

class TemplateImage extends Model
{
    protected $table = 'tok_ls_template_images';

    protected $fillable = [
        'template_id',
        'path',
        'alt',
    ];

    public function template()
    {
        return $this->belongsTo(LsTemplate::class, 'template_id');
    }

    /**
     * When a TemplateImage row is deleted, also delete the underlying file.
     */
    protected static function booted()
    {
        static::deleting(function (TemplateImage $image) {
            if ($image->path && Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }
        });
    }
}