<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentMessage extends Model
{
    protected $table = 'comment_messages';

    protected $fillable = [
        'comment_id',
        'author_id',
        'body',
    ];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}