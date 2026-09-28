<?php

namespace Database\Seeders;

use App\Enums\QuorumType;
use App\Enums\RaffleAnimation;
use App\Models\Assembly;
use App\Models\Raffle;
use App\Models\Teacher;
use App\Models\User;
use App\Support\RaffleFilters;
use Illuminate\Database\Seeder;

/**
 * A past assembly, already closed, with its attendance and three raffles, so
 * the history has records to show. The open assembly of DemoAssemblySeeder
 * stays untouched for trying out the desk. For local development only.
 */
class DemoRaffleSeeder extends Seeder
{
    public function run(): void
    {
        if (Raffle::query()->exists()) {
            return;
        }

        $admin = User::firstWhere('email', 'admin@simac.test');
        $day = today()->subMonth();

        $assembly = Assembly::create([
            'name' => 'Asamblea Extraordinaria',
            'date' => $day,
            'location' => 'Coliseo Municipal · Aguazul',
            'quorum_type' => QuorumType::Percentage,
            'quorum_value' => 50,
            'opened_by' => $admin?->id,
        ]);

        // Everyone came but the last four on the roll.
        $teachers = Teacher::query()->orderBy('id')->get();

        foreach ($teachers->slice(0, -4)->values() as $index => $teacher) {
            $assembly->attendances()->create([
                'teacher_id' => $teacher->id,
                'checked_in_at' => $day->copy()->setTime(8, 0)->addMinutes($index * 3),
                'registered_by' => $admin?->id,
            ]);
        }

        foreach ([
            ['Bono de 200.000 pesos', new RaffleFilters(unionMembersOnly: true, presentOnly: true), 1, RaffleAnimation::Wheel, '10:15'],
            ['Mercado familiar', new RaffleFilters(presentOnly: true), 3, RaffleAnimation::Drum, '10:40'],
            ['Bicicleta todoterreno', new RaffleFilters(presentOnly: true), 1, RaffleAnimation::Reveal, '11:05'],
        ] as [$prize, $filters, $winners, $animation, $time]) {
            Raffle::factory()
                ->for($assembly)
                ->drawnAmong($filters->participants($assembly)->get()->shuffle())
                ->create([
                    'prize' => $prize,
                    'winners_count' => $winners,
                    'animation' => $animation,
                    'filters' => $filters->toArray(),
                    'filter_description' => $filters->describe($assembly),
                    'quorum_met' => $assembly->quorum()?->isMet(),
                    'drawn_by' => $admin?->id,
                    'drawn_at' => $day->copy()->setTimeFromTimeString($time),
                ]);
        }

        $assembly->attendances()->update(['checked_out_at' => $day->copy()->setTime(12, 30)]);
        $assembly->update(['closed_at' => $day->copy()->setTime(12, 45)]);
    }
}
