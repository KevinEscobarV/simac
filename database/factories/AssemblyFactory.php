<?php

namespace Database\Factories;

use App\Enums\QuorumType;
use App\Models\Assembly;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assembly>
 */
class AssemblyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Asamblea '.fake()->randomElement(['General Ordinaria', 'Extraordinaria', 'de Delegados', 'Regional']),
            'date' => today(),
            'location' => null,
            'quorum_type' => null,
            'quorum_value' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_at' => now(),
        ]);
    }

    public function withQuorum(QuorumType $type, float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'quorum_type' => $type,
            'quorum_value' => $value,
        ]);
    }
}
