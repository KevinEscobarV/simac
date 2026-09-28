<?php

namespace App\Actions\Raffles;

use App\Enums\RaffleAnimation;
use App\Models\Assembly;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\RaffleEntry;
use App\Models\User;
use App\Support\RaffleFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Random\Engine\Secure;
use Random\Randomizer;

class DrawRaffle
{
    /**
     * Draw the winners on the server, over the real list in the database,
     * with the operating system's cryptographic generator, and seal the
     * record before anything is shown: the animation only presents a result
     * already decided. The raffle is left loaded on the screens, waiting for
     * the "Go!".
     *
     * @throws ValidationException when the filters leave too few participants,
     *                             or another raffle is still on screen
     */
    public function handle(User $drawnBy, RaffleFilters $filters, string $prize, int $winnersCount, RaffleAnimation $animation): Raffle
    {
        return DB::transaction(function () use ($drawnBy, $filters, $prize, $winnersCount, $animation): Raffle {
            // The assembly in progress is where the raffle happens: the record
            // says which one and whether it had quorum at that moment.
            $assembly = Assembly::current();

            if ($filters->presentOnly && $assembly === null) {
                throw ValidationException::withMessages([
                    'filters' => __('There is no open assembly, so nobody counts as present. Open one or draw among everyone.'),
                ]);
            }

            /** @var list<int> $participants */
            $participants = $filters->participants($assembly)->pluck('id')->all();

            $this->ensureEnoughParticipants(count($participants), $winnersCount, $filters);

            $winners = array_slice((new Randomizer(new Secure))->shuffleArray($participants), 0, $winnersCount);

            $raffle = Raffle::create([
                'assembly_id' => $assembly?->id,
                'prize' => $prize,
                'winners_count' => $winnersCount,
                'animation' => $animation,
                'filters' => $filters->toArray(),
                'filter_description' => $filters->describe($assembly),
                'participants_count' => count($participants),
                // The fact, not the rule: adjusting the quorum later does not
                // change what the record says.
                'quorum_met' => $assembly?->quorum()?->isMet(),
                'drawn_by' => $drawnBy->id,
            ]);

            $this->recordEntries($raffle, $participants, $winners);

            Projection::current()->prepare($raffle);

            return $raffle;
        });
    }

    /**
     * @throws ValidationException
     */
    private function ensureEnoughParticipants(int $participants, int $winners, RaffleFilters $filters): void
    {
        if ($participants < 2) {
            throw ValidationException::withMessages(['participants' => $filters->presentOnly
                ? __('At least 2 teachers who are present and meet the filters are needed. Check the attendance.')
                : __('At least 2 participants are needed. Adjust the filters.')]);
        }

        if ($participants <= $winners) {
            throw ValidationException::withMessages([
                'participants' => __('There are :participants participants for :winners winners: there must be more participants than winners.', [
                    'participants' => $participants,
                    'winners' => $winners,
                ]),
            ]);
        }
    }

    /**
     * Everyone in the draw goes into the record, so it can be audited later;
     * the winners with the order in which they are revealed.
     *
     * @param  list<int>  $participants
     * @param  list<int>  $winners
     */
    private function recordEntries(Raffle $raffle, array $participants, array $winners): void
    {
        $positions = array_flip($winners);

        foreach (array_chunk($participants, 500) as $chunk) {
            RaffleEntry::query()->insert(array_map(fn (int $teacher): array => [
                'raffle_id' => $raffle->id,
                'teacher_id' => $teacher,
                'winner_position' => isset($positions[$teacher]) ? $positions[$teacher] + 1 : null,
            ], $chunk));
        }
    }
}
