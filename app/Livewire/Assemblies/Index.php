<?php

namespace App\Livewire\Assemblies;

use App\Actions\Assemblies\AdjustQuorum;
use App\Actions\Assemblies\CloseAssembly;
use App\Actions\Assemblies\DeleteAssembly;
use App\Actions\Assemblies\OpenAssembly;
use App\Actions\Assemblies\ReopenAssembly;
use App\Actions\Attendance\CheckIn;
use App\Actions\Attendance\CheckOut;
use App\Actions\Attendance\ResolveTeacher;
use App\Actions\Attendance\VoidAttendance;
use App\Concerns\ReportsOnProperty;
use App\Enums\AttendanceMovement;
use App\Livewire\Forms\AssemblyForm;
use App\Livewire\Forms\QuorumForm;
use App\Models\Assembly;
use App\Models\Teacher;
use App\Support\Quorum;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The assembly in progress: counters, quorum and the roll to take attendance
 * on. Below, the assemblies already closed.
 *
 * @property-read Assembly|null $current
 * @property-read Quorum|null $quorum
 * @property-read Collection<int, Assembly> $pastAssemblies
 * @property-read int $unionMembers
 * @property-read int $rollCount
 */
class Index extends Component
{
    use ReportsOnProperty, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** "todos", "presentes", "salieron" or "sin-registrar". */
    #[Url(as: 'filtro', except: 'todos')]
    public string $filter = 'todos';

    public AssemblyForm $form;

    public QuorumForm $quorumForm;

    #[Locked]
    public ?Teacher $voiding = null;

    #[Locked]
    public ?Assembly $deleting = null;

    #[Locked]
    public ?string $deletionBlocker = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filter'], true)) {
            $this->resetPage();
            $this->resetErrorBag('search');
        }
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

    /**
     * @return Collection<int, Assembly>
     */
    #[Computed]
    public function pastAssemblies(): Collection
    {
        return Assembly::query()
            ->closed()
            ->withCount('attendances')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function unionMembers(): int
    {
        return Teacher::query()->unionMembers()->count();
    }

    #[Computed]
    public function rollCount(): int
    {
        return Teacher::count();
    }

    public function startOpening(): void
    {
        $this->authorize('create', Assembly::class);

        $this->form->start();

        Flux::modal('assembly-open')->show();
    }

    public function open(OpenAssembly $openAssembly): void
    {
        $this->authorize('create', Assembly::class);

        $assembly = $openAssembly->handle(auth()->user(), $this->form->attributesFrom($this->form->validate()));

        Flux::modal('assembly-open')->close();
        Flux::toast(variant: 'success', text: __('":name" is open. You can start registering check-ins.', ['name' => $assembly->name]));

        $this->refreshAssembly();
    }

    public function editQuorum(): void
    {
        $assembly = $this->currentOrFail();

        $this->authorize('update', $assembly);

        $this->quorumForm->edit($assembly);

        Flux::modal('assembly-quorum')->show();
    }

    public function saveQuorum(AdjustQuorum $adjustQuorum): void
    {
        $assembly = $this->currentOrFail();

        $this->authorize('update', $assembly);

        $adjustQuorum->handle($assembly, $this->quorumForm->attributesFrom($this->quorumForm->validate()));

        Flux::modal('assembly-quorum')->close();
        Flux::toast(variant: 'success', text: __('Quorum updated.'));

        $this->refreshAssembly();
    }

    public function confirmClosing(): void
    {
        $this->authorize('close', $this->currentOrFail());

        Flux::modal('assembly-close')->show();
    }

    public function close(CloseAssembly $closeAssembly): void
    {
        $assembly = $this->currentOrFail();

        $this->authorize('close', $assembly);

        $closeAssembly->handle($assembly);

        Flux::modal('assembly-close')->close();
        Flux::toast(text: __('":name" was closed.', ['name' => $assembly->name]));

        $this->reset('search', 'filter');
        $this->refreshAssembly();
    }

    public function reopen(Assembly $assembly, ReopenAssembly $reopenAssembly): void
    {
        $this->authorize('reopen', $assembly);

        $reopenAssembly->handle($assembly);

        Flux::toast(variant: 'success', text: __('":name" is open again.', ['name' => $assembly->name]));

        $this->refreshAssembly();
    }

    public function confirmDeletion(Assembly $assembly, DeleteAssembly $deleteAssembly): void
    {
        $this->authorize('delete', $assembly);

        $this->resetErrorBag('assembly');
        $this->deleting = $assembly;
        $this->deletionBlocker = $deleteAssembly->blocker($assembly);

        Flux::modal('assembly-delete')->show();
    }

    public function delete(DeleteAssembly $deleteAssembly): void
    {
        $this->authorize('delete', $this->deleting);

        $deleteAssembly->handle($this->deleting);

        Flux::modal('assembly-delete')->close();
        Flux::toast(text: __('":name" was deleted.', ['name' => $this->deleting->name]));

        $this->deleting = null;
        $this->refreshAssembly();
    }

    public function checkIn(Teacher $teacher, CheckIn $checkIn): void
    {
        $this->reportingOn('search', fn () => $this->registerCheckIn($teacher, $checkIn));
    }

    /**
     * Enter in the search box checks in whoever the ID number, code or name
     * points to, so the panel works with a keyboard or a barcode reader.
     */
    public function checkInFromSearch(ResolveTeacher $resolveTeacher, CheckIn $checkIn): void
    {
        $this->reportingOn('search', fn () => $this->registerCheckIn($resolveTeacher->handle($this->search), $checkIn));

        $this->reset('search');
    }

    public function checkOut(Teacher $teacher, CheckOut $checkOut): void
    {
        $this->reportingOn('search', function () use ($teacher, $checkOut): void {
            $assembly = $this->currentOrFail();

            $this->authorize('registerAttendance', $assembly);

            $checkOut->handle($assembly, $teacher);

            Flux::toast(text: __('Check-out of :name registered.', ['name' => $teacher->name]));

            $this->refreshAssembly();
        });
    }

    public function confirmVoiding(Teacher $teacher): void
    {
        $this->authorize('registerAttendance', $this->currentOrFail());

        $this->voiding = $teacher;

        Flux::modal('attendance-void')->show();
    }

    public function voidAttendance(VoidAttendance $voidAttendance): void
    {
        $assembly = $this->currentOrFail();

        $this->authorize('registerAttendance', $assembly);

        $voidAttendance->handle($assembly, $this->voiding);

        Flux::modal('attendance-void')->close();
        Flux::toast(text: __('The record of :name was voided.', ['name' => $this->voiding->name]));

        $this->voiding = null;
        $this->refreshAssembly();
    }

    /**
     * A desk or another administrator changed something: render again with
     * fresh counters, quorum and roll.
     */
    #[On('echo-private:assemblies,AttendanceChanged')]
    #[On('echo-private:assemblies,AssemblyChanged')]
    public function refreshLive(): void
    {
        $this->refreshAssembly();
    }

    public function render(): View
    {
        return view('livewire.assemblies.index', [
            'teachers' => $this->current ? $this->roll($this->current) : null,
        ])->title(__('Assemblies'));
    }

    /**
     * The roll with each teacher's attendance at the assembly.
     *
     * @return LengthAwarePaginator<int, Teacher>
     */
    private function roll(Assembly $assembly): LengthAwarePaginator
    {
        return Teacher::query()
            ->with(['school', 'attendances' => fn (Relation $attendances) => $attendances->whereBelongsTo($assembly)])
            ->search($this->search)
            ->when($this->filter === 'presentes', fn (Builder $query) => $query->presentAt($assembly))
            ->when($this->filter === 'salieron', fn (Builder $query) => $query->whereHas(
                'attendances',
                fn (Builder $attendances) => $attendances->whereBelongsTo($assembly)->whereNotNull('checked_out_at'),
            ))
            ->when($this->filter === 'sin-registrar', fn (Builder $query) => $query->whereDoesntHave(
                'attendances',
                fn (Builder $attendances) => $attendances->whereBelongsTo($assembly),
            ))
            ->orderByName()
            ->paginate(25);
    }

    private function registerCheckIn(Teacher $teacher, CheckIn $checkIn): void
    {
        $assembly = $this->currentOrFail();

        $this->authorize('registerAttendance', $assembly);

        $movement = $checkIn->handle($assembly, $teacher, auth()->user());

        Flux::toast(variant: $movement === AttendanceMovement::AlreadyPresent ? null : 'success', text: match ($movement) {
            AttendanceMovement::AlreadyPresent => __(':name was already present.', ['name' => $teacher->name]),
            AttendanceMovement::Reentry => __(':name came back in.', ['name' => $teacher->name]),
            default => __('Check-in of :name registered.', ['name' => $teacher->name]),
        });

        $this->refreshAssembly();
    }

    /**
     * The open assembly, or a clear message if someone closed it meanwhile.
     *
     * @throws ValidationException
     */
    private function currentOrFail(): Assembly
    {
        return $this->current ?? throw ValidationException::withMessages([
            'assembly' => __('There is no open assembly.'),
        ]);
    }

    /**
     * Computed properties are memoized for the whole request: forget them
     * after a write so counters and quorum are read again.
     */
    private function refreshAssembly(): void
    {
        unset($this->current, $this->quorum, $this->pastAssemblies);
    }
}
