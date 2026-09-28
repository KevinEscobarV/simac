<?php

namespace Database\Factories;

use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assembly_id' => Assembly::factory(),
            'teacher_id' => Teacher::factory(),
            'checked_in_at' => now(),
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_out_at' => now(),
        ]);
    }
}
