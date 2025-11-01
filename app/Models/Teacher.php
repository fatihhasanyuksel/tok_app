<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'password', 'active', 'is_admin'];
    protected $hidden   = ['password'];
    protected $casts    = [
        'active'   => 'boolean',
        'is_admin' => 'boolean',
    ];
}