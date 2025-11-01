<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Roles (single-table multi-role) */
    public const ROLE_STUDENT = 'student';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_ADMIN   = 'admin';

    /**
     * Mass assignable attributes.
     * NOTE: Adding 'role' and 'supervising_teacher_id' is non-breaking; they’ll be ignored
     * if the columns don’t exist yet (just don’t try to mass-assign before migration).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',                    // 👈 new (will be added by migration)
        'supervising_teacher_id',  // 👈 optional link student → teacher
    ];

    /**
     * Attributes hidden from array/JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'password'               => 'hashed',
            'role'                   => 'string',   // 👈 new
            'supervising_teacher_id' => 'integer',  // 👈 new (safe even if column absent)
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // ToK Feedback App Relationships & Helpers
    // ─────────────────────────────────────────────────────────────

    /** A student’s submissions (exhibition + essay). */
    public function submissions()
    {
        return $this->hasMany(\App\Models\Submission::class, 'student_id');
    }

    /** Quick helper to fetch a specific submission type. */
    public function submission(string $type)
    {
        return $this->submissions()->where('type', $type)->first();
    }

    /** A teacher’s students (if supervising_teacher_id column exists). */
    public function students()
    {
        return $this->hasMany(self::class, 'supervising_teacher_id');
    }

    /** A student’s supervising teacher (if supervising_teacher_id column exists). */
    public function teacher()
    {
        return $this->belongsTo(self::class, 'supervising_teacher_id');
    }

    /** General messages sent by this user (teacher or student). */
    public function sentGeneralMessages()
    {
        return $this->hasMany(\App\Models\GeneralMessage::class, 'sender_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Role helpers (non-breaking; return false if role column absent)
    // ─────────────────────────────────────────────────────────────

    public function isStudent(): bool
    {
        return ($this->role ?? self::ROLE_STUDENT) === self::ROLE_STUDENT;
    }

    public function isTeacher(): bool
    {
        return ($this->role ?? null) === self::ROLE_TEACHER;
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? null) === self::ROLE_ADMIN;
    }

    /** Teacher OR Admin. */
    public function isStaff(): bool
    {
        $r = $this->role ?? null;
        return $r === self::ROLE_TEACHER || $r === self::ROLE_ADMIN;
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes (safe even before the role migration)
    // ─────────────────────────────────────────────────────────────

    public function scopeStudents($q)
    {
        return $q->where('role', self::ROLE_STUDENT);
    }

    public function scopeTeachers($q)
    {
        return $q->where('role', self::ROLE_TEACHER);
    }

    public function scopeAdmins($q)
    {
        return $q->where('role', self::ROLE_ADMIN);
    }
}