<?php

namespace App\Livewire\Raffles;

use App\Actions\Raffles\DeclareWinnerAbsent;
use App\Actions\Raffles\DrawRaffle;
use App\Concerns\ReportsOnProperty;
use App\Enums\RaffleAnimation;
use App\Livewire\Forms\RaffleForm;
use App\Models\Assembly;
use App\Models\City;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\School;
use App\Models\Teacher;
use App\Support\Quorum;
use Closure;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * A new raffle and, while one is on the screens, the console that drives
 * them. Both read the server's state, so reloading the page in the middle of
 * the event brings the console back exactly where it was.
 *
 * @property-read Projection $projection
 * @property-read Assembly|null $assembly
 * @property-read Quorum|null $quorum
 * @property-read Collection<int, City> $cities
 * @property-read Collection<int, School> $schools
 * @property-read int $participantsCount
 * @property-read Collection<int, Teacher> $participantSample
 * @property-read string $filterDescription
 * @property-read Collection<int, Teacher> $winners
 * @property-read Collection<int, Teacher> $forfeits
 * @property-read int $screens
 */
class Create extends Component
{
    use ReportsOnProperty;

    /** How many names the summary lists before "and N more". */
    public const int SAMPLE = 48;

    public RaffleForm $form;

    /** "See before": the console shows the current winner before the screen does. */
    public bool $peeking = false;

    /**
     * Raffles are held among those in the room: with an assembly open, the
     * form starts with "only those present" checked. Without one the option
     * cannot be used, so it starts unchecked.
     */
    public function mount(): void
    {
        $this->form->present_only = $this->assembly !== null;
    }

    public function updated(string $property): void
    {
        if ($property === 'form.city_id') {
            $this->form->school_id = '';
        }

        $this->resetErrorBag('form.draw');
    }

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

    #[Computed]
    public function quorum(): ?Quorum
    {
        return $this->assembly?->quorum();
    }

    /**
     * @return Collection<int, City>
     */
    #[Computed]
    public function cities(): Collection
    {
        return City::query()->orderByName()->get();
    }

    /**
     * The schools of the chosen municipality.
     *
     * @return Collection<int, School>
     */
    #[Computed]
    public function schools(): Collection
    {
        if (! ctype_digit($this->form->city_id)) {
            return new Collection;
        }

        return School::query()->where('city_id', $this->form->city_id)->orderByName()->get();
    }

    #[Computed]
    public function participantsCount(): int
    {
        return $this->form->filters()->participants($this->assembly)->count();
    }

    /**
     * @return Collection<int, Teacher>
     */
    #[Computed]
    public function participantSample(): Collection
    {
        return $this->form->filters()->participants($this->assembly)->orderByName()->limit(self::SAMPLE)->get();
    }

    #[Computed]
    public function filterDescription(): string
    {
        return $this->form->filters()->describe($this->assembly);
    }

    /**
     * The winners of the raffle on screen, in the order they are revealed.
     *
     * @return Collection<int, Teacher>
     */
    #[Computed]
    public function winners(): Collection
    {
        return $this->projection->raffle?->winners()->with(['school', 'city'])->get() ?? new Collection;
    }

    /**
     * The winners of the raffle on screen who did not come forward.
     *
     * @return Collection<int, Teacher>
     */
    #[Computed]
    public function forfeits(): Collection
    {
        return $this->projection->raffle?->forfeits()->get() ?? new Collection;
    }

    #[Computed]
    public function screens(): int
    {
        return Projection::connectedScreens();
    }

    /**
     * The server draws and seals the record; the raffle waits on the screens
     * for the "Go!".
     */
    public function draw(DrawRaffle $drawRaffle): void
    {
        $this->authorize('create', Raffle::class);

        $validated = $this->form->validate();

        $this->reportingOn('form.draw', fn (): Raffle => $drawRaffle->handle(
            auth()->user(),
            $this->form->filters(),
            $validated['prize'],
            (int) $validated['winners_count'],
            RaffleAnimation::from($validated['animation']),
        ));

        $this->form->forNextRaffle();
        $this->peeking = false;
        $this->projectionChanged();
    }

    /**
     * "Go!", or "Next winner" once one is on screen.
     */
    public function launch(): void
    {
        $this->control(fn (Projection $projection) => $projection->launch());

        $this->peeking = false;
    }

    public function repeat(): void
    {
        $this->control(fn (Projection $projection) => $projection->repeat());
    }

    /**
     * "Did not come forward": the winner on screen loses the prize, leaves the
     * room on record, and the screens animate their replacement right away.
     */
    public function declareAbsent(DeclareWinnerAbsent $declareWinnerAbsent): void
    {
        $this->control(fn () => $declareWinnerAbsent->handle(auth()->user()));

        $this->peeking = false;

        Flux::modal('winner-absent')->close();
    }

    public function release(): void
    {
        $this->control(fn (Projection $projection) => $projection->release());

        $this->peeking = false;

        Flux::modal('projection-release')->close();
    }

    public function peek(): void
    {
        $this->authorize('control', Projection::class);

        $this->peeking = true;
    }

    /**
     * A screen finished an animation, or the assembly changed: read the
     * projection, the counters and the quorum again.
     */
    #[On('echo-private:projection,ProjectionUpdated')]
    #[On('echo-private:assemblies,AttendanceChanged')]
    #[On('echo-private:assemblies,AssemblyChanged')]
    public function refreshLive(): void
    {
        $this->refreshComputed();
    }

    public function render(): View
    {
        return view('livewire.raffles.create')
            ->title($this->projection->isLive() ? __('Raffle on screen') : __('New raffle'));
    }

    /**
     * A console action. A conflict (a screen finished, another administrator
     * acted first) is only a notice: the console shows the state as it is.
     *
     * @param  Closure(Projection): void  $change
     */
    private function control(Closure $change): void
    {
        $this->authorize('control', Projection::class);

        try {
            $change($this->projection);
        } catch (ValidationException $exception) {
            Flux::toast(variant: 'danger', text: $exception->validator->errors()->first());
        }

        $this->projectionChanged();
    }

    /**
     * Other components of the page, like the "LIVE" mark of the navigation,
     * do not get the broadcast of a change made from this page.
     */
    private function projectionChanged(): void
    {
        $this->refreshComputed();
        $this->dispatch('projection-changed');
    }

    /**
     * Computed properties are memoized for the whole request: forget them
     * after a write.
     */
    private function refreshComputed(): void
    {
        unset($this->projection, $this->assembly, $this->quorum, $this->participantsCount, $this->participantSample, $this->filterDescription, $this->winners, $this->forfeits, $this->screens);
    }
}
