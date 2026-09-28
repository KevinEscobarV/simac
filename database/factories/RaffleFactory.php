<?php

namespace Database\Factories;

use App\Enums\RaffleAnimation;
use App\Models\Raffle;
use App\Models\Teacher;
use App\Support\RaffleFilters;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * @extends Factory<Raffle>
 */
class RaffleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assembly_id' => null,
            'prize' => fake()->randomElement(['Televisor de 50 pulgadas', 'Bicicleta', 'Tableta', 'Mercado familiar', 'Bono de 200.000 pesos']),
            'winners_count' => 1,
            'animation' => fake()->randomElement(RaffleAnimation::cases()),
            'filters' => (new RaffleFilters)->toArray(),
            'filter_description' => __('All teachers'),
            'participants_count' => 0,
            'quorum_met' => null,
            'drawn_by' => null,
        ];
    }

    /**
     * A sealed record among these teachers: the first ones won, in order.
     *
     * @param  Collection<int, Teacher>  $teachers
     */
    public function drawnAmong(Collection $teachers): static
    {
        return $this->state(['participants_count' => $teachers->count()])
            ->afterCreating(function (Raffle $raffle) use ($teachers): void {
                $raffle->participants()->attach($teachers->values()->mapWithKeys(fn (Teacher $teacher, int $index): array => [
                    $teacher->id => ['winner_position' => $index < $raffle->winners_count ? $index + 1 : null],
                ]));
            });
    }
}
