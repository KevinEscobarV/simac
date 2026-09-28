<?php

namespace App\Livewire\Screen;

use App\Enums\ProjectionPhase;
use App\Models\Assembly;
use App\Models\Projection;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The projection screen: the only surface the room sees, so it has no
 * interface. It rests, announces the raffle, runs the animation when the
 * console gives the order and shows the winner. The result is sealed in the
 * record before the first frame: the animation decides nothing.
 *
 * Each phase only gets what it can show: the current winner reaches the page
 * when their animation starts, never before.
 *
 * @property-read Projection $projection
 * @property-read Assembly|null $assembly
 * @property-read Teacher|null $currentWinner
 * @property-read Collection<int, Teacher> $earlierWinners
 * @property-read list<array{id: int, name: string, short: string}> $reel
 */
class Index extends Component
{
    /** How many names the wheel and the drum carry, the winner among them. */
    public const int REEL = 48;

    #[Computed]
    public function projection(): Projection
    {
        return Projection::current();
    }

    #[Computed]
    public function assembly(): ?Assembly
    {
        return Assembly::current();
    }

    /**
     * The winner being animated or on screen, once the "Go!" was given.
     */
    #[Computed]
    public function currentWinner(): ?Teacher
    {
        $projection = $this->projection;

        if (! in_array($projection->phase, [ProjectionPhase::Animating, ProjectionPhase::Winner], true)) {
            return null;
        }

        return $projection->raffle?->winners()
            ->wherePivot('winner_position', $projection->winner_position)
            ->with('school.city')
            ->first();
    }

    /**
     * The winners already revealed before the current one.
     *
     * @return Collection<int, Teacher>
     */
    #[Computed]
    public function earlierWinners(): Collection
    {
        $projection = $this->projection;

        return $projection->raffle?->winners()
            ->wherePivot('winner_position', '<', $projection->winner_position)
            ->with('school.city')
            ->get() ?? new Collection;
    }

    /**
     * The names the animation goes through: the current winner and, around
     * them, participants still in the draw. With long lists it is a sample;
     * the count on screen says how many take part.
     *
     * The shuffle is seeded with the raffle and the position so every screen
     * shows the same wheel. It only arranges names on screen: the winners were
     * drawn with the cryptographic generator when the record was sealed.
     *
     * @return list<array{id: int, name: string, short: string}>
     */
    #[Computed]
    public function reel(): array
    {
        $winner = $this->currentWinner;
        $raffle = $this->projection->raffle;

        if ($winner === null || $raffle === null) {
            return [];
        }

        $position = $this->projection->winner_position;
        $randomizer = new Randomizer(new Mt19937($raffle->id * 1000 + $position));

        /** @var list<int> $others */
        $others = $raffle->participants()
            ->whereKeyNot($winner->id)
            ->where(fn (Builder $entries) => $entries
                ->whereNull('raffle_entries.winner_position')
                ->orWhere('raffle_entries.winner_position', '>', $position))
            ->pluck('teachers.id')
            ->all();

        $ids = $randomizer->shuffleArray([
            ...array_slice($randomizer->shuffleArray($others), 0, self::REEL - 1),
            $winner->id,
        ]);

        $teachers = Teacher::withTrashed()->findMany($ids)->keyBy('id');

        return array_map(fn (int $id): array => [
            'id' => $id,
            'name' => $teachers[$id]->name,
            'short' => Str::limit($teachers[$id]->short_name, 17, '…'),
        ], $ids);
    }

    /**
     * The screen reports that it is on, so the console counts it.
     */
    #[Renderless]
    public function beat(string $screen): void
    {
        $this->authorize('watch', Projection::class);

        Projection::recordScreen(Str::limit($screen, 40, ''));
    }

    /**
     * The animation of this screen reached the end: the winner is public.
     */
    public function finish(int $attempt): void
    {
        $this->authorize('watch', Projection::class);

        $this->projection->finish($attempt);

        $this->refreshComputed();
    }

    /**
     * The console gave an order: render the stage again. A new attempt
     * restarts the animation. (The projector does not follow the assemblies
     * channel; the name at rest is read again with every change.)
     */
    #[On('echo-private:projection,ProjectionUpdated')]
    public function refreshLive(): void
    {
        $this->refreshComputed();
    }

    public function render(): View
    {
        return view('livewire.screen.index')
            ->layout('layouts::stage')
            ->title(__('Projection screen'));
    }

    /**
     * Computed properties are memoized for the whole request: forget them
     * after a change.
     */
    private function refreshComputed(): void
    {
        unset($this->projection, $this->assembly, $this->currentWinner, $this->earlierWinners, $this->reel);
    }
}
