<?php

namespace Database\Factories;

use App\Models\CheckpointDeadline;
use App\Models\CheckpointStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckpointDeadlineFactory extends Factory
{
    protected $model = CheckpointDeadline::class;

    public function definition(): array
    {
        $workType = $this->faker->randomElement(CheckpointStatus::WORK_TYPES);
        $stage    = $this->faker->randomElement(CheckpointStatus::STAGES);

        return [
            'work_type' => $workType,
            'stage'     => $stage,
            'deadline'  => now()->addDays($this->faker->numberBetween(3, 30))->startOfDay(),
            'created_at'=> now(),
            'updated_at'=> now(),
        ];
    }

    public function for(string $workType, string $stage, \DateTimeInterface $date): self
    {
        return $this->state(fn () => [
            'work_type' => $workType,
            'stage'     => $stage,
            'deadline'  => $date,
        ]);
    }
}