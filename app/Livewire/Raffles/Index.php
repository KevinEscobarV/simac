<?php

namespace App\Livewire\Raffles;

use App\Models\Assembly;
use App\Models\Projection;
use App\Models\Raffle;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The history of raffles: the latest record stands out and the rest follow,
 * newest first, filtered by assembly.
 *
 * @property-read Projection $projection
 * @property-read Collection<int, Assembly> $assemblies
 * @property-read bool $hasRafflesWithoutAssembly
 */
class Index extends Component
{
    use WithPagination;

    /** The filter value for raffles drawn while no assembly was open. */
    public const string WITHOUT_ASSEMBLY = 'ninguna';

    /** An assembly id, WITHOUT_ASSEMBLY or empty for every record. */
    #[Url(as: 'jornada', except: '')]
    public string $assembly = '';

    public function updatedAssembly(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function projection(): Projection
    {
        return Projection::current();
    }

    /**
     * The assemblies that had raffles, the latest first.
     *
     * @return Collection<int, Assembly>
     */
    #[Computed]
    public function assemblies(): Collection
    {
        return Assembly::query()->has('raffles')->withCount('raffles')->latest('date')->latest('id')->get();
    }

    #[Computed]
    public function hasRafflesWithoutAssembly(): bool
    {
        return Raffle::query()->whereNull('assembly_id')->exists();
    }

    /**
     * A raffle was drawn, or the screens revealed a winner.
     */
    #[On('echo-private:projection,ProjectionUpdated')]
    public function refreshLive(): void
    {
        unset($this->projection, $this->assemblies, $this->hasRafflesWithoutAssembly);
    }

    public function render(): View
    {
        $raffles = Raffle::query()
            ->with(['assembly', 'winners.school', 'winners.city'])
            ->when($this->assembly === self::WITHOUT_ASSEMBLY, fn (Builder $query) => $query->whereNull('assembly_id'))
            ->when(ctype_digit($this->assembly), fn (Builder $query) => $query->where('assembly_id', (int) $this->assembly))
            ->latest()
            ->latest('id')
            ->paginate(20);

        return view('livewire.raffles.index', [
            'raffles' => $raffles,
        ])->title(__('Raffle history'));
    }
}
