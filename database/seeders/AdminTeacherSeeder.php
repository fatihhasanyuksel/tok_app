<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;

class AdminTeacherSeeder extends Seeder
{
    public function run(): void
    {
        Teacher::updateOrCreate(
            ['email' => 'hasan.yuksel@australianschool.ae'],
            [
                'name'     => 'Hasan Yuksel',
                'password' => Hash::make('Password123!'),
                'active'   => 1,
                'is_admin' => 1,
            ]
        );
    }
}