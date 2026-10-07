<?php

namespace App\Actions\Raffles;

use App\Actions\Attendance\CheckOut;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\RaffleEntry;
use App\Models\Teacher;
use App\Models\User;
use App\Support\RaffleFilters;
use Illuminate\Validation\ValidationException;
use Random\Engine\Secure;
use Random\Randomizer;

class DeclareWinnerAbsent
{
    public function __construct(private CheckOut $checkOut) {}

    /**
     * The winner on screen did not come forward. They stay in the record as
     * absent, with the time and who declared it; if they were still checked
     * in, the desk counts them as gone; and another winner for the same
     * position is drawn with the cryptographic generator among the record's
     * participants still in the running, sealed before the screens animate it.
     * All of it or nothing: it happens in the projection's own change.
     *
     * @throws ValidationException when no winner is on screen, no screen is on, or nobody is left to draw
     */
    public function handle(User $declaredBy): void
    {
        Projection::current()->replaceWinner(function (Raffle $raffle, int $position) use ($declaredBy): void {
            $absent = $raffle->winners()->wherePivot('winner_position', $position)->sole();
            $replacement = $this->drawReplacement($raffle);

            // The absent winner frees the position before the replacement takes it.
            RaffleEntry::query()->where('raffle_id', $raffle->id)->where('teacher_id', $absent->id)->update([
                'winner_position' => null,
                'forfeited_position' => $position,
                'forfeited_at' => now(),
                'forfeited_by' => $declaredBy->id,
            ]);

            RaffleEntry::query()->where('raffle_id', $raffle->id)->where('teacher_id', $replacement)->update([
                'winner_position' => $position,
            ]);

            $this->checkOutIfPresent($raffle, $absent);
        });
    }

    /**
     * One of those still in the running: the record's participants who have
     * not won nor been declared absent, still on the roll and, when the raffle
     * was only for those present, still in the room.
     *
     * @throws ValidationException when nobody is left
     */
    private function drawReplacement(Raffle $raffle): int
    {
        $assembly = $raffle->assembly;
        $presentOnly = RaffleFilters::fromArray($raffle->filters)->presentOnly;

        /** @var list<int> $candidates */
        $candidates = $raffle->participants()
            ->wherePivotNull('winner_position')
            ->wherePivotNull('forfeited_position')
            ->whereNull('teachers.deleted_at')
            ->when($presentOnly && $assembly !== null, fn ($participants) => $participants->presentAt($assembly))
            ->pluck('teachers.id')
            ->all();

        if ($candidates === []) {
            throw ValidationException::withMessages([
                'projection' => __('Nobody is left in this raffle to take the prize.'),
            ]);
        }

        return $candidates[(new Randomizer(new Secure))->getInt(0, count($candidates) - 1)];
    }

    /**
     * Someone still checked in has in fact left the room: the desk counts
     * them as gone, so the quorum is right and they stay out of the raffles
     * for those present. If they come back, the desk registers the re-entry.
     */
    private function checkOutIfPresent(Raffle $raffle, Teacher $teacher): void
    {
        $assembly = $raffle->assembly;

        $present = $assembly !== null
            && $assembly->isOpen()
            && $assembly->attendances()->whereBelongsTo($teacher)->whereNull('checked_out_at')->exists();

        if ($present) {
            $this->checkOut->handle($assembly, $teacher);
        }
    }
}
