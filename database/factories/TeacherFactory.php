<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state. Codes start at 90000, so a test can
     * give any shorter code to a teacher without colliding.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->name(),
            'document_number' => fake()->unique()->numerify('11########'),
            'code' => fake()->unique()->numerify('9####'),
            'is_union_member' => true,
        ];
    }

    public function nonMember(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_union_member' => false,
        ]);
    }
}
