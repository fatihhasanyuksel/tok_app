<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Optional demo user
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Always create or update the admin teacher account
        $this->call([
            AdminTeacherSeeder::class,
            CheckpointDeadlinesSeeder::class,
            CheckpointStatusesSeeder::class,
        ]);
    }
}