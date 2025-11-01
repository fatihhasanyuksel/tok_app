<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'name'     => $this->faker->name(),
            'email'    => $this->faker->unique()->safeEmail(),
            // Keep a known password for tests
            'password' => Hash::make('Password123!'),
            'active'   => true,
            'is_admin' => false,
        ];
    }

    /** Inactive state */
    public function inactive(): static
    {
        return $this->state(fn() => ['active' => false]);
    }

    /** Admin state (still active) */
    public function admin(): static
    {
        return $this->state(fn() => ['is_admin' => true, 'active' => true]);
    }
}