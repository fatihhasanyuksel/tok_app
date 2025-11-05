<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CheckpointStatus;

class CheckpointStatusesSeeder extends Seeder
{
    public function run(): void
    {
        // Demo data for first 20 students (adjust to your roster)
        $studentIds = range(1, 20);

        foreach ($studentIds as $sid) {
            foreach (CheckpointStatus::WORK_TYPES as $type) {
                // Ensure one status row per (student, work_type)
                CheckpointStatus::updateOrCreate(
                    ['student_id' => $sid, 'work_type' => $type],
                    ['status' => CheckpointStatus::STAGE_DRAFT_1, 'updated_by' => null]
                );
            }
        }
    }
}