<?php

namespace App\Livewire;

use App\Models\Assembly;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\Teacher;
use App\Support\Quorum;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The administrator's home: the assembly in progress, a raffle on screen,
 * the numbers of the roll and the latest record, with the way to each post.
 * Each part only shows to who may use it, and follows the event live.
 *
 * @property-read Assembly|null $assembly
 * @property-read Quorum|null $quorum
 * @property-read Projection $projection
 * @property-read Raffle|null $latestRaffle
 * @property-read array{teachers: int, members: int, assemblies: int, raffles: int} $stats
 */
class Dashboard extends Component
{
    #[Computed]
    public function assembly(): ?Assembly
    {
        return Assembly::query()->open()->withAttendanceCounts()->latest('id')->first();
    }

    #[Computed]
    public function quorum(): ?Quorum
    {
        return $this->assembly?->quorum();
    }

    #[Computed]
    public function projection(): Projection
    {
        return Projection::current();
    }

    #[Computed]
    public function latestRaffle(): ?Raffle
    {
        return Raffle::query()->with(['assembly', 'winners.school.city'])->latest()->latest('id')->first();
    }

    /**
     * @return array{teachers: int, members: int, assemblies: int, raffles: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'teachers' => Teacher::count(),
            'members' => Teacher::query()->unionMembers()->count(),
            'assemblies' => Assembly::count(),
            'raffles' => Raffle::count(),
        ];
    }

    /**
     * Attendance, the assembly and the projection change the page. Each
     * channel is only joined by who may follow it.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        $user = auth()->user();

        return array_merge(
            $user->can('followLive', Assembly::class) ? [
                'echo-private:assemblies,AttendanceChanged' => 'refreshLive',
                'echo-private:assemblies,AssemblyChanged' => 'refreshLive',
            ] : [],
            $user->can('watch', Projection::class) ? [
                'echo-private:projection,ProjectionUpdated' => 'refreshLive',
            ] : [],
        );
    }

    public function refreshLive(): void
    {
        unset($this->assembly, $this->quorum, $this->projection, $this->latestRaffle, $this->stats);
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
