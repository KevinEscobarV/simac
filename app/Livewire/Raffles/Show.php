<?php

namespace App\Livewire\Raffles;

use App\Models\Projection;
use App\Models\Raffle;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A record: what was raffled, among whom and who won, with the full list of
 * participants to audit it.
 *
 * @property-read Projection $projection
 * @property-read Collection<int, Teacher> $winners
 */
class Show extends Component
{
    use WithPagination;

    public Raffle $raffle;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(Raffle $raffle): void
    {
        $this->raffle = $raffle->load(['assembly', 'drawer']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function projection(): Projection
    {
        return Projection::current();
    }

    /**
     * The winners already public, in order.
     *
     * @return Collection<int, Teacher>
     */
    #[Computed]
    public function winners(): Collection
    {
        return $this->raffle->load('winners.school', 'winners.city')->publicWinners($this->projection);
    }

    /**
     * The screens revealed another winner, or released the raffle.
     */
    #[On('echo-private:projection,ProjectionUpdated')]
    public function refreshLive(): void
    {
        unset($this->projection, $this->winners);
    }

    public function render(): View
    {
        $participants = $this->raffle->participants()
            ->with(['school', 'city'])
            ->search($this->search)
            ->orderByName()
            ->paginate(50);

        return view('livewire.raffles.show', [
            'participants' => $participants,
        ])->title(__('Record No. :number', ['number' => $this->raffle->id]));
    }
}
