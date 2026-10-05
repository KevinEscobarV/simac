<?php

namespace App\Livewire\Desk;

use App\Actions\Attendance\CheckIn;
use App\Actions\Attendance\CheckOut;
use App\Actions\Attendance\ResolveTeacher;
use App\Actions\Attendance\UndoMovement;
use App\Concerns\ReportsOnProperty;
use App\Enums\AttendanceMovement;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Support\Quorum;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The registration desk: whoever receives the teachers at the door looks up
 * the person in front of them and marks their check-in or check-out. One
 * field, always focused, a confirmation that can be read from afar and a way
 * to undo a slip without calling the administrator.
 *
 * @property-read Assembly|null $current
 * @property-read Quorum|null $quorum
 * @property-read int $rollCount
 * @property-read Collection<int, Teacher> $results
 * @property-read Collection<int, Attendance> $recentMovements
 */
class Index extends Component
{
    use ReportsOnProperty;

    /** How many matches the desk lists: it serves one person, it does not browse the roll. */
    public const int RESULTS = 8;

    public string $search = '';

    /**
     * The movement just registered, shown big until it fades out.
     *
     * @var array{id: int, tone: string, title: string, name: string, detail: string, teacher_id: int, movement: string|null}|null
     */
    #[Locked]
    public ?array $confirmation = null;

    #[Locked]
    public int $movements = 0;

    public function updatedSearch(): void
    {
        $this->resetErrorBag();
    }

    #[Computed]
    public function current(): ?Assembly
    {
        return Assembly::query()->open()->withAttendanceCounts()->latest('id')->first();
    }

    #[Computed]
    public function quorum(): ?Quorum
    {
        return $this->current?->quorum();
    }

    #[Computed]
    public function rollCount(): int
    {
        return Teacher::count();
    }

    /**
     * Whoever the search matches, with their attendance, the exact code first.
     * Without a search term there is nothing to list.
     *
     * @return Collection<int, Teacher>
     */
    #[Computed]
    public function results(): Collection
    {
        $assembly = $this->current;

        if ($assembly === null || trim($this->search) === '') {
            return new Collection;
        }

        return Teacher::query()
            ->with(['school.city', 'attendances' => fn (Relation $attendances) => $attendances->whereBelongsTo($assembly)])
            ->search($this->search)
            ->exactCodeFirst($this->search)
            ->orderByName()
            ->limit(self::RESULTS + 1)
            ->get();
    }

    /**
     * The last check-ins and check-outs at the assembly, from every desk.
     *
     * @return Collection<int, Attendance>
     */
    #[Computed]
    public function recentMovements(): Collection
    {
        return $this->current?->attendances()
            ->with('teacher.school')
            ->latest('updated_at')
            ->latest('id')
            ->limit(8)
            ->get() ?? new Collection;
    }

    /**
     * Enter: checks in whoever the code, ID number or name points to. The
     * server resolves the key, so a barcode reader never waits for the list.
     */
    public function checkInByKey(string $key, ResolveTeacher $resolveTeacher, CheckIn $checkIn): void
    {
        $this->clearScreen();

        $this->reportingOn('search', fn () => $this->registerCheckIn($resolveTeacher->handle($key), $checkIn));
    }

    /**
     * Shift + Enter: the same for a check-out.
     */
    public function checkOutByKey(string $key, ResolveTeacher $resolveTeacher, CheckOut $checkOut): void
    {
        $this->clearScreen();

        $this->reportingOn('search', fn () => $this->registerCheckOut($resolveTeacher->handle($key), $checkOut));
    }

    public function checkIn(Teacher $teacher, CheckIn $checkIn): void
    {
        $this->reportingOn('search', fn () => $this->registerCheckIn($teacher, $checkIn));
    }

    public function checkOut(Teacher $teacher, CheckOut $checkOut): void
    {
        $this->reportingOn('search', fn () => $this->registerCheckOut($teacher, $checkOut));
    }

    /**
     * Revert the movement on display: a check-in voided, a re-entry or a
     * check-out reverted.
     */
    public function undo(UndoMovement $undoMovement): void
    {
        $movement = AttendanceMovement::tryFrom($this->confirmation['movement'] ?? '');
        $teacher = Teacher::withTrashed()->find($this->confirmation['teacher_id'] ?? null);

        if ($movement === null || $teacher === null) {
            return;
        }

        $this->reportingOn('search', function () use ($undoMovement, $teacher, $movement): void {
            $assembly = $this->startMovement();

            $undoMovement->handle($assembly, $teacher, $movement);

            $this->confirm($assembly, $teacher, null, __('Movement undone'));
        });
    }

    /**
     * The confirmation fades out on its own, unless a newer one replaced it.
     */
    public function dismiss(int $id): void
    {
        if ($this->confirmation !== null && $this->confirmation['id'] === $id) {
            $this->confirmation = null;
        }
    }

    /**
     * A desk or the administrator changed something: render again with fresh
     * counters, quorum and statuses. If the assembly was closed, whatever was
     * typed goes too.
     */
    #[On('echo-private:assemblies,AttendanceChanged')]
    #[On('echo-private:assemblies,AssemblyChanged')]
    public function refreshLive(): void
    {
        $this->refreshAssembly();

        if ($this->current === null) {
            $this->reset('search');
            $this->clearScreen();
        }
    }

    public function render(): View
    {
        return view('livewire.desk.index')
            ->layout('layouts::kiosk')
            ->title(__('Registration desk'));
    }

    private function registerCheckIn(Teacher $teacher, CheckIn $checkIn): void
    {
        $assembly = $this->startMovement();

        $movement = $checkIn->handle($assembly, $teacher, auth()->user());

        $this->confirm($assembly, $teacher, $movement, match ($movement) {
            AttendanceMovement::CheckIn => __('Check-in registered'),
            AttendanceMovement::Reentry => __('Re-entry registered'),
            default => __('Already checked in'),
        });
    }

    private function registerCheckOut(Teacher $teacher, CheckOut $checkOut): void
    {
        $assembly = $this->startMovement();

        $movement = $checkOut->handle($assembly, $teacher);

        $this->confirm($assembly, $teacher, $movement, __('Check-out registered'));
    }

    /**
     * The open assembly for a new movement, clearing what the previous one
     * left on screen.
     *
     * @throws ValidationException when someone closed the assembly meanwhile
     */
    private function startMovement(): Assembly
    {
        $this->clearScreen();

        $assembly = $this->current;

        if ($assembly === null) {
            throw ValidationException::withMessages(['assembly' => __('There is no open assembly.')]);
        }

        $this->authorize('registerAttendance', $assembly);

        return $assembly;
    }

    /**
     * Show what happened and get the field ready for the next person.
     */
    private function confirm(Assembly $assembly, Teacher $teacher, ?AttendanceMovement $movement, string $title): void
    {
        $attendance = $assembly->attendances()->whereBelongsTo($teacher)->first();

        $this->confirmation = [
            'id' => ++$this->movements,
            'tone' => match ($movement) {
                AttendanceMovement::CheckIn, AttendanceMovement::Reentry => 'in',
                AttendanceMovement::CheckOut => 'out',
                default => 'notice',
            },
            'title' => $title,
            'name' => $teacher->name,
            'detail' => $this->timesOf($attendance),
            'teacher_id' => $teacher->id,
            'movement' => $movement?->isUndoable() ? $movement->value : null,
        ];

        $this->reset('search');
        $this->refreshAssembly();
        $this->dispatch('desk-ready');
    }

    /**
     * A new attempt replaces the confirmation or the error on display.
     */
    private function clearScreen(): void
    {
        $this->confirmation = null;
        $this->resetErrorBag();
    }

    private function timesOf(?Attendance $attendance): string
    {
        if ($attendance === null) {
            return __('No record at this assembly');
        }

        $times = __('in :time', ['time' => $attendance->checked_in_at->translatedFormat('g:i a')]);

        if ($attendance->checked_out_at !== null) {
            $times .= ' · '.__('out :time', ['time' => $attendance->checked_out_at->translatedFormat('g:i a')]);
        }

        return $times;
    }

    /**
     * Computed properties are memoized for the whole request: forget them
     * after a write so counters, quorum and lists are read again.
     */
    private function refreshAssembly(): void
    {
        unset($this->current, $this->quorum, $this->results, $this->recentMovements);
    }
}
