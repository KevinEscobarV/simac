<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * The single row, with no event: the system shows only SIMAC.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Setting::ROW,
            'event_title' => null,
            'event_subtitle' => null,
            'event_image_path' => null,
        ];
    }

    public function withEvent(): static
    {
        return $this->state(fn (): array => [
            'event_title' => 'Juegos Deportivos del Magisterio '.now()->year,
            'event_subtitle' => fake()->city().', '.fake()->numberBetween(1, 28).' de octubre',
        ]);
    }
}
