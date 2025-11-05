<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckpointStagesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['key' => 'no_submission', 'label' => 'No submission', 'display_order' => 10, 'is_active' => true],
            ['key' => 'draft_1',       'label' => 'Draft 1',       'display_order' => 20, 'is_active' => true],
            ['key' => 'draft_2',       'label' => 'Draft 2',       'display_order' => 30, 'is_active' => true],
            ['key' => 'final',         'label' => 'Final',         'display_order' => 40, 'is_active' => true],
            ['key' => 'approved',      'label' => 'Approved',      'display_order' => 50, 'is_active' => true],
        ];

        foreach ($rows as $r) {
            DB::table('checkpoint_stages')->updateOrInsert(
                ['key' => $r['key']],
                ['label' => $r['label'], 'display_order' => $r['display_order'], 'is_active' => $r['is_active']]
            );
        }
    }
}