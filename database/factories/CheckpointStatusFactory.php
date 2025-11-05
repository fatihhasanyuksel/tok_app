<?php

namespace Database\Factories;

use App\Models\CheckpointStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckpointStatusFactory extends Factory
{
    protected $model = CheckpointStatus::class;

    public function definition(): array
    {
        $workType = $this->faker->randomElement(CheckpointStatus::WORK_TYPES);
        $stage    = $this->faker->randomElement(CheckpointStatus::STAGES);

        return [
            'student_id' => $this->faker->numberBetween(1, 50),   // adjust if you have real students
            'work_type'  => $workType,
            'status'     => $stage,
            'updated_by' => null,                                  // or a teacher id if you want
            'created_at' => now()->subDays($this->faker->numberBetween(1, 20)),
            'updated_at' => now(),
        ];
    }

    public function forStudent(int $studentId): self
    {
        return $this->state(fn () => ['student_id' => $studentId]);
    }

    public function workType(string $type): self
    {
        return $this->state(fn () => ['work_type' => $type]);
    }

    public function stage(string $stage): self
    {
        return $this->state(fn () => ['status' => $stage]);
    }
}