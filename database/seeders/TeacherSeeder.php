<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Main admin teacher (Hasan)
        Teacher::updateOrCreate(
            ['email' => 'hasan.yuksel@australianschool.ae'],
            [
                'name'     => 'Hasan Yuksel',
                'password' => Hash::make('Asad@2025'),
                'active'   => true,
                'is_admin' => true,
            ]
        );

        // ✅ Second teacher (Adam)
        Teacher::updateOrCreate(
            ['email' => 'adam.kelly@australianschool.ae'],
            [
                'name'     => 'Adam Kelly',
                'password' => Hash::make('Asad@2025'),
                'active'   => true,
                'is_admin' => false,
            ]
        );
    }
}