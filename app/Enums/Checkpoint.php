<?php

namespace App\Enums;

final class Checkpoint
{
    // Workspace types
    public const TYPE_EXHIBITION = 'exhibition';
    public const TYPE_ESSAY      = 'essay';

    /** @return string[] */
    public static function types(): array
    {
        return [self::TYPE_EXHIBITION, self::TYPE_ESSAY];
    }

    // Canonical stage codes (must match both deadlines & statuses)
    public const STAGE_NONE          = 'none';
    public const STAGE_DRAFT1        = 'draft1';
    public const STAGE_DRAFT2        = 'draft2';
    public const STAGE_DRAFT3        = 'draft3';
    public const STAGE_STUDENT_FINAL = 'student_final';
    public const STAGE_APPROVED      = 'approved';

    /** @return string[] */
    public static function stages(): array
    {
        return [
            self::STAGE_NONE,
            self::STAGE_DRAFT1,
            self::STAGE_DRAFT2,
            self::STAGE_DRAFT3,
            self::STAGE_STUDENT_FINAL,
            self::STAGE_APPROVED,
        ];
    }

    /** Human labels for UI dropdowns */
    public static function stageLabels(): array
    {
        return [
            self::STAGE_NONE          => 'No submission',
            self::STAGE_DRAFT1        => 'Draft 1 submitted',
            self::STAGE_DRAFT2        => 'Draft 2 submitted',
            self::STAGE_DRAFT3        => 'Draft 3 submitted',
            self::STAGE_STUDENT_FINAL => 'Student final version submitted',
            self::STAGE_APPROVED      => 'Approved by teacher',
        ];
    }
}