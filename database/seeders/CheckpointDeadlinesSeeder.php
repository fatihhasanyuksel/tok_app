<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CheckpointDeadline;
use App\Models\CheckpointStatus;
use Illuminate\Support\Carbon;

class CheckpointDeadlinesSeeder extends Seeder
{
    public function run(): void
    {
        $stages = CheckpointStatus::STAGES;
        $types  = CheckpointStatus::WORK_TYPES;

        $rows = [];
        $base = Carbon::now()->startOfDay();

        foreach ($types as $t) {
            foreach ($stages as $i => $stage) {
                // Space deadlines 7 days apart per stage for demonstration
                $rows[] = [
                    'work_type' => $t,
                    'stage'     => $stage,
                    'deadline'  => $base->copy()->addDays(($i + 1) * 7),
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ];
            }
        }

        // idempotent
        foreach ($rows as $row) {
            CheckpointDeadline::updateOrCreate(
                ['work_type' => $row['work_type'], 'stage' => $row['stage']],
                ['deadline'  => $row['deadline']]
            );
        }
    }
}