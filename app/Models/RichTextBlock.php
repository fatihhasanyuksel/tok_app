<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RichTextBlock extends Model
{
    use HasFactory;

    protected $table = 'rich_text_blocks';

    /**
     * Mass-assignable fields.
     */
    protected $fillable = [
        'owner_type',
        'owner_id',
        'version_label',
        'pm_json',
        'html',
        'word_count',
        'char_count',
        'created_by',
        'updated_by',
    ];

    /**
     * Casts for attributes.
     */
    protected $casts = [
        'pm_json'   => 'array',
        'owner_id'  => 'integer',
        'word_count'=> 'integer',
        'char_count'=> 'integer',
    ];

    /**
     * Simple lookup scope (e.g., RichTextBlock::for('exhibition', $id)->latest()->first()).
     */
    public function scopeFor($query, string $ownerType, int $ownerId)
    {
        return $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);
    }

    /**
     * Compute text metrics from plain text.
     */
    public static function computeMetrics(string $plainText): array
    {
        // Basic metrics; refine later if needed.
        $chars = mb_strlen(trim($plainText));
        // Split on any whitespace; filter out empties for a robust word count.
        $words = preg_split('/\s+/u', trim($plainText), -1, PREG_SPLIT_NO_EMPTY);
        return [
            'word_count' => $words ? count($words) : 0,
            'char_count' => $chars,
        ];
    }

    /**
     * Audit: who created/updated (nullable in Phase 1).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}