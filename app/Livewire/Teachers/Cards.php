<?php

namespace App\Livewire\Teachers;

use App\Models\City;
use App\Models\School;
use App\Support\CardSelection;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Choose which cards to print, by municipality, school and membership, and
 * see the first sheet. Printing opens the sheet in a tab of its own.
 *
 * @property-read Collection<int, City> $cities
 * @property-read Collection<int, School> $schools
 */
class Cards extends Component
{
    #[Url(as: 'municipio', except: '')]
    public string $city = '';

    #[Url(as: 'colegio', except: '')]
    public string $school = '';

    /** "si", "no" or empty for everyone. */
    #[Url(as: 'afiliacion', except: '')]
    public string $membership = '';

    public function updatedCity(): void
    {
        $this->school = '';
    }

    /**
     * @return Collection<int, City>
     */
    #[Computed]
    public function cities(): Collection
    {
        return City::query()->has('teachers')->orderByName()->get();
    }

    /**
     * @return Collection<int, School>
     */
    #[Computed]
    public function schools(): Collection
    {
        if (! ctype_digit($this->city)) {
            return new Collection;
        }

        return School::query()->where('city_id', (int) $this->city)->has('teachers')->orderByName()->get();
    }

    public function selection(): CardSelection
    {
        return CardSelection::fromQuery([
            'municipio' => $this->city,
            'colegio' => $this->school,
            'afiliacion' => $this->membership,
        ]);
    }

    public function render(): View
    {
        $selection = $this->selection();

        return view('livewire.teachers.cards', [
            'count' => $selection->teachers()->count(),
            'preview' => $selection->teachers()->limit(CardSelection::PER_SHEET)->get(),
            'printUrl' => route('teachers.cards.print', $selection->toQuery()),
        ])->title(__('Cards'));
    }
}
